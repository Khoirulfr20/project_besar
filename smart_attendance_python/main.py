import os
import io
import json
from typing import Optional, List, Tuple
from datetime import datetime

import numpy as np
import cv2
from PIL import Image

from fastapi import FastAPI, File, UploadFile, HTTPException, status
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import mysql.connector
from mysql.connector import Error as MySQLError

# ==========================
# ENV
# ==========================
load_dotenv()

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "smart_attendance"),
}

# Threshold confidence LBPH (semakin rendah semakin baik, 0-100)
# Nilai kecil = match yang baik, nilai besar = tidak match
FACE_CONFIDENCE_THRESHOLD = float(os.getenv("FACE_DISTANCE_THRESHOLD", "45.0"))

# ==========================
# APP
# ==========================
app = FastAPI(title="SmartAttendance Face API - LBPH")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

def get_db():
    """Get database connection with error handling"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except MySQLError as e:
        print(f"❌ DATABASE ERROR: {e}")
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail={
                "success": False,
                "message": "Database connection failed",
                "error": str(e)
            }
        )

# ==========================
# FACE DETECTION & LBPH
# ==========================
CASCADE = cv2.CascadeClassifier(
    cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
)

# Initialize LBPH Face Recognizer
LBPH_RECOGNIZER = cv2.face.LBPHFaceRecognizer_create(
    radius=1,
    neighbors=8,
    grid_x=8,
    grid_y=8
)

def detect_face(cv2_img) -> Optional[Tuple[np.ndarray, Tuple[int, int, int, int]]]:
    """
    Detect face in image and return face region + bounding box
    Returns: (face_gray, (x, y, w, h)) or None
    """
    try:
        gray = cv2.cvtColor(cv2_img, cv2.COLOR_BGR2GRAY)
        gray = cv2.equalizeHist(gray)

        faces = CASCADE.detectMultiScale(
            gray,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(80, 80),
            flags=cv2.CASCADE_SCALE_IMAGE
        )

        if len(faces) == 0:
            return None

        # Get largest face
        x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
        face = gray[y:y+h, x:x+w]
        
        # Resize to standard size for consistency
        face = cv2.resize(face, (200, 200))

        return face, (x, y, w, h)
    
    except Exception as e:
        print(f"❌ FACE DETECTION ERROR: {e}")
        return None

def extract_embedding(cv2_img):
    """
    Extract face for LBPH training/recognition
    Returns: normalized face image as list (for JSON storage)
    """
    result = detect_face(cv2_img)
    if result is None:
        return None
    
    face, _ = result
    
    # Convert to list for JSON storage
    # Store as flattened normalized values
    face_normalized = face.astype("float32") / 255.0
    return face_normalized.flatten().tolist()

def prepare_face_for_lbph(embedding_list) -> np.ndarray:
    """
    Convert stored embedding back to image format for LBPH
    """
    face_array = np.array(embedding_list, dtype=np.float32)
    face_array = (face_array * 255.0).astype(np.uint8)
    face_img = face_array.reshape(200, 200)
    return face_img

def train_lbph_model(faces_data: List[dict]) -> Optional[cv2.face.LBPHFaceRecognizer]:
    """
    Train LBPH model with registered faces
    faces_data: [{"user_id": int, "face_encoding": str}, ...]
    Returns: trained recognizer or None
    """
    if len(faces_data) == 0:
        return None
    
    try:
        faces = []
        labels = []
        
        for data in faces_data:
            try:
                embedding = json.loads(data["face_encoding"])
                face_img = prepare_face_for_lbph(embedding)
                faces.append(face_img)
                labels.append(data["user_id"])
            except (json.JSONDecodeError, ValueError) as e:
                print(f"⚠️ Invalid encoding for user {data['user_id']}: {e}")
                continue
        
        if len(faces) == 0:
            return None
        
        # Train LBPH recognizer
        recognizer = cv2.face.LBPHFaceRecognizer_create(
            radius=1,
            neighbors=8,
            grid_x=8,
            grid_y=8
        )
        recognizer.train(faces, np.array(labels))
        
        print(f"✅ LBPH model trained with {len(faces)} faces")
        return recognizer
        
    except Exception as e:
        print(f"❌ LBPH TRAINING ERROR: {e}")
        return None

def confidence_to_percentage(lbph_confidence: float) -> float:
    """
    Convert LBPH confidence (distance) to percentage
    LBPH confidence: 0 = perfect match, higher = worse match
    Output: 0-100 where 100 = perfect match
    """
    # Normalize: map [0, threshold] to [100, 0]
    if lbph_confidence <= 0:
        return 100.0
    
    percentage = max(0.0, 100.0 - (lbph_confidence / FACE_CONFIDENCE_THRESHOLD * 100.0))
    return min(100.0, percentage)

# ==========================
# HEALTH CHECK
# ==========================
@app.get("/health", status_code=status.HTTP_200_OK)
def health():
    """Health check endpoint"""
    try:
        # Test database connection
        conn = get_db()
        cur = conn.cursor()
        cur.execute("SELECT 1")
        cur.fetchone()
        cur.close()
        conn.close()
        
        return JSONResponse(
            status_code=status.HTTP_200_OK,
            content={
                "success": True,
                "status": "healthy",
                "database": "connected",
                "face_recognition": "LBPH",
                "timestamp": datetime.now().isoformat()
            }
        )
    except Exception as e:
        return JSONResponse(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            content={
                "success": False,
                "status": "unhealthy",
                "database": "disconnected",
                "error": str(e),
                "timestamp": datetime.now().isoformat()
            }
        )

# ==========================
# ENCODE FACE
# ==========================
@app.post("/encode")
async def encode(image: UploadFile = File(...)):
    """Encode face from uploaded image"""
    
    # Validate file type
    if not image.content_type.startswith("image/"):
        return JSONResponse(
            status_code=status.HTTP_400_BAD_REQUEST,
            content={
                "success": False,
                "message": "File harus berupa gambar (JPEG, PNG, dll)",
                "error": "INVALID_FILE_TYPE"
            }
        )
    
    try:
        # Read and process image
        content = await image.read()
        
        if len(content) == 0:
            return JSONResponse(
                status_code=status.HTTP_400_BAD_REQUEST,
                content={
                    "success": False,
                    "message": "File gambar kosong",
                    "error": "EMPTY_FILE"
                }
            )
        
        img = Image.open(io.BytesIO(content)).convert("RGB")
        cv2_img = cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)

        # Extract face embedding
        embedding = extract_embedding(cv2_img)
        
        if embedding is None:
            return JSONResponse(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                content={
                    "success": False,
                    "message": "Wajah tidak terdeteksi di gambar",
                    "error": "FACE_NOT_DETECTED",
                    "hint": "Pastikan wajah terlihat jelas dan pencahayaan cukup"
                }
            )

        return JSONResponse(
            status_code=status.HTTP_200_OK,
            content={
                "success": True,
                "message": "Wajah berhasil dienkode",
                "data": {
                    "embedding": embedding,
                    "quality_score": 1.0,
                    "embedding_size": len(embedding),
                    "method": "LBPH"
                }
            }
        )
    
    except Exception as e:
        print(f"❌ ENCODE ERROR: {e}")
        return JSONResponse(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            content={
                "success": False,
                "message": "Terjadi kesalahan saat memproses gambar",
                "error": "PROCESSING_ERROR",
                "details": str(e)
            }
        )

# ==========================
# RECOGNIZE FACE
# ==========================
@app.post("/recognize")
async def recognize(image: UploadFile = File(...)):
    """Recognize face from uploaded image using LBPH"""
    
    # Validate file type
    if not image.content_type.startswith("image/"):
        return JSONResponse(
            status_code=status.HTTP_400_BAD_REQUEST,
            content={
                "success": False,
                "message": "File harus berupa gambar (JPEG, PNG, dll)",
                "error": "INVALID_FILE_TYPE"
            }
        )
    
    try:
        # Read and process image
        content = await image.read()
        
        if len(content) == 0:
            return JSONResponse(
                status_code=status.HTTP_400_BAD_REQUEST,
                content={
                    "success": False,
                    "message": "File gambar kosong",
                    "error": "EMPTY_FILE"
                }
            )
        
        img = Image.open(io.BytesIO(content)).convert("RGB")
        cv2_img = cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)

        # Detect face
        result = detect_face(cv2_img)
        
        if result is None:
            print("❌ GAGAL: Wajah tidak terdeteksi di gambar")
            return JSONResponse(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                content={
                    "success": False,
                    "message": "Wajah tidak terdeteksi di gambar",
                    "error": "FACE_NOT_DETECTED",
                    "hint": "Pastikan wajah terlihat jelas dan pencahayaan cukup"
                }
            )
        
        probe_face, _ = result

        # Get registered faces from database
        try:
            conn = get_db()
            cur = conn.cursor(dictionary=True)
            cur.execute("""
                SELECT user_id, face_encoding
                FROM face_data
                WHERE is_active = 1 AND is_primary = 1
            """)
            rows = cur.fetchall()
            cur.close()
            conn.close()
        except MySQLError as e:
            print(f"❌ DATABASE ERROR: {e}")
            return JSONResponse(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                content={
                    "success": False,
                    "message": "Gagal mengakses database",
                    "error": "DATABASE_ERROR",
                    "details": str(e)
                }
            )

        print(f"📊 Jumlah wajah terdaftar: {len(rows)}")

        if len(rows) == 0:
            print("⚠️ TIDAK ADA wajah terdaftar di database!")
            return JSONResponse(
                status_code=status.HTTP_404_NOT_FOUND,
                content={
                    "success": False,
                    "message": "Tidak ada wajah terdaftar di database",
                    "error": "NO_REGISTERED_FACES",
                    "hint": "Silakan daftarkan wajah terlebih dahulu"
                }
            )

        # Train LBPH model with registered faces
        recognizer = train_lbph_model(rows)
        
        if recognizer is None:
            return JSONResponse(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                content={
                    "success": False,
                    "message": "Gagal melatih model pengenalan wajah",
                    "error": "MODEL_TRAINING_FAILED"
                }
            )

        # Predict using LBPH
        try:
            label, confidence = recognizer.predict(probe_face)
            print(f"🎯 LBPH Prediction: User {label}, confidence={confidence:.2f}, threshold={FACE_CONFIDENCE_THRESHOLD}")
        except Exception as e:
            print(f"❌ LBPH PREDICTION ERROR: {e}")
            return JSONResponse(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                content={
                    "success": False,
                    "message": "Gagal mengenali wajah",
                    "error": "PREDICTION_ERROR",
                    "details": str(e)
                }
            )

        # Check if confidence is good enough
        # Lower confidence = better match in LBPH
        if confidence > FACE_CONFIDENCE_THRESHOLD:
            return JSONResponse(
                status_code=status.HTTP_404_NOT_FOUND,
                content={
                    "success": False,
                    "message": "Wajah tidak dikenali",
                    "error": "FACE_NOT_RECOGNIZED",
                    "data": {
                        "distance": round(confidence, 2),
                        "threshold": FACE_CONFIDENCE_THRESHOLD,
                        "confidence": round(confidence_to_percentage(confidence), 2)
                    },
                    "hint": "Wajah tidak cocok dengan data yang terdaftar"
                }
            )

        # Success - face recognized
        return JSONResponse(
            status_code=status.HTTP_200_OK,
            content={
                "success": True,
                "message": "Wajah berhasil dikenali",
                "data": {
                    "user_id": int(label),
                    "distance": round(confidence, 2),
                    "confidence": round(confidence_to_percentage(confidence), 2),
                    "threshold": FACE_CONFIDENCE_THRESHOLD,
                    "method": "LBPH"
                }
            }
        )
    
    except Exception as e:
        print(f"❌ RECOGNIZE ERROR: {e}")
        return JSONResponse(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            content={
                "success": False,
                "message": "Terjadi kesalahan saat mengenali wajah",
                "error": "PROCESSING_ERROR",
                "details": str(e)
            }
        )

# ==========================
# ERROR HANDLERS
# ==========================
@app.exception_handler(HTTPException)
async def http_exception_handler(request, exc):
    """Handle HTTP exceptions"""
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "success": False,
            "message": exc.detail if isinstance(exc.detail, str) else exc.detail.get("message", "Unknown error"),
            "error": "HTTP_ERROR",
            "status_code": exc.status_code
        }
    )

@app.exception_handler(Exception)
async def general_exception_handler(request, exc):
    """Handle general exceptions"""
    print(f"❌ UNHANDLED ERROR: {exc}")
    return JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content={
            "success": False,
            "message": "Terjadi kesalahan pada server",
            "error": "INTERNAL_SERVER_ERROR",
            "details": str(exc)
        }
    )

# ==========================
# RUN
# ==========================
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)