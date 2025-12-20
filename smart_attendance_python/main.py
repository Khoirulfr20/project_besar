import os
import io
import json
from typing import Optional, List
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

FACE_DISTANCE_THRESHOLD = float(os.getenv("FACE_DISTANCE_THRESHOLD", "12.0"))

# ==========================
# APP
# ==========================
app = FastAPI(title="SmartAttendance Face API")

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
# FACE UTILS
# ==========================
CASCADE = cv2.CascadeClassifier(
    cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
)

def extract_embedding(cv2_img):
    """Extract face embedding from image"""
    try:
        gray = cv2.cvtColor(cv2_img, cv2.COLOR_BGR2GRAY)
        gray = cv2.equalizeHist(gray)

        faces = CASCADE.detectMultiScale(
            gray,
            scaleFactor=1.1,
            minNeighbors=3,
            minSize=(80, 80)
        )

        if len(faces) == 0:
            return None

        x, y, w, h = max(faces, key=lambda f: f[2] * f[3])
        face = gray[y:y+h, x:x+w]

        face = cv2.resize(face, (64, 64))
        face = face.astype("float32") / 255.0

        return face.flatten().tolist()
    
    except Exception as e:
        print(f"❌ EXTRACT EMBEDDING ERROR: {e}")
        return None

def euclidean(a, b) -> float:
    """Calculate euclidean distance between two vectors"""
    return float(np.linalg.norm(np.array(a) - np.array(b)))

def confidence(dist):
    """Calculate confidence score from distance"""
    return max(0.0, min(1.0, 1.0 - (dist / FACE_DISTANCE_THRESHOLD)))

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

        # Extract embedding
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
                    "embedding_size": len(embedding)
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
    """Recognize face from uploaded image"""
    
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
        probe = extract_embedding(cv2_img)
        
        if probe is None:
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

        # Find best match
        best_user = None
        best_dist = None

        for r in rows:
            try:
                vec = json.loads(r["face_encoding"])
                d = euclidean(probe, vec)
                print(f"   User {r['user_id']}: distance = {d:.2f}")
                
                if best_dist is None or d < best_dist:
                    best_dist = d
                    best_user = r["user_id"]
            except json.JSONDecodeError as e:
                print(f"⚠️ Invalid encoding for user {r['user_id']}: {e}")
                continue

        print(f"🎯 Best: User {best_user}, dist={best_dist:.2f}, threshold={FACE_DISTANCE_THRESHOLD}")

        # Check if match is good enough
        if best_dist is None or best_dist > FACE_DISTANCE_THRESHOLD:
            return JSONResponse(
                status_code=status.HTTP_404_NOT_FOUND,
                content={
                    "success": False,
                    "message": "Wajah tidak dikenali",
                    "error": "FACE_NOT_RECOGNIZED",
                    "data": {
                        "distance": round(best_dist, 2) if best_dist else None,
                        "threshold": FACE_DISTANCE_THRESHOLD,
                        "confidence": round(confidence(best_dist) * 100, 2) if best_dist else 0
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
                    "user_id": best_user,
                    "distance": round(best_dist, 2),
                    "confidence": round(confidence(best_dist) * 100, 2),
                    "threshold": FACE_DISTANCE_THRESHOLD
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