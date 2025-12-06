import os
import io
import json
from typing import Optional, List
from datetime import datetime

# ==========================
# IMPORT LIBRARY
# ==========================
import numpy as np
import cv2
from PIL import Image

from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import mysql.connector
import base64


# ==========================
# LOAD ENV
# ==========================
load_dotenv()

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "smart_attendance"),
}

FACE_DISTANCE_THRESHOLD = float(os.getenv("FACE_DISTANCE_THRESHOLD", "0.6"))
DEBUG = os.getenv("DEBUG", "false").lower() == "true"


def debug_print(*args):
    if DEBUG:
        print(*args)


# ==========================
# FASTAPI APP
# ==========================
app = FastAPI(title="SmartAttendance Face API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # sementara boleh semua
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)


# ==========================
# FACE UTILS
# ==========================
CASCADE_PATH = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
FACE_CASCADE = cv2.CascadeClassifier(CASCADE_PATH)


def compute_quality_score(cv2_img) -> float:
    gray = cv2.cvtColor(cv2_img, cv2.COLOR_BGR2GRAY)
    fm = cv2.Laplacian(gray, cv2.CV_64F).var()
    score = (fm - 50.0) / (500.0 - 50.0)
    score = max(0.0, min(1.0, score))
    return float(score)


def extract_face_embedding(cv2_img) -> Optional[List[float]]:
    gray = cv2.cvtColor(cv2_img, cv2.COLOR_BGR2GRAY)
    faces = FACE_CASCADE.detectMultiScale(gray, scaleFactor=1.2, minNeighbors=5)

    if len(faces) == 0:
        debug_print("No face detected")
        return None

    x, y, w, h = sorted(faces, key=lambda f: f[2] * f[3], reverse=True)[0]
    face_region = gray[y:y + h, x:x + w]

    if face_region.size == 0:
        debug_print("Empty face region")
        return None

    face_resized = cv2.resize(face_region, (32, 32))
    norm = face_resized.astype("float32") / 255.0
    embedding = norm.flatten()
    return embedding.tolist()


def euclidean_distance(e1: List[float], e2: List[float]) -> float:
    a = np.array(e1, dtype=np.float32)
    b = np.array(e2, dtype=np.float32)
    return float(np.linalg.norm(a - b))


def distance_to_confidence(dist: float, max_dist: float = None) -> float:
    if max_dist is None:
        max_dist = FACE_DISTANCE_THRESHOLD
    raw = 1.0 - (dist / max_dist)
    return float(max(0.0, min(1.0, raw)))


# ==========================
# HEALTH
# ==========================
@app.get("/health")
async def health():
    return {"status": "ok"}


# ==========================
# REGISTER FACE SIMPLE TEST
# ==========================
@app.post("/register-face")
async def register_face(file: UploadFile = File(...)):
    filename = f"face_{datetime.now().strftime('%Y%m%d%H%M%S')}.jpg"
    with open(filename, "wb") as f:
        f.write(await file.read())

    return {
        "success": True,
        "message": "Register endpoint OK (photo received)",
        "filename": filename
    }


# ==========================
# /encode (MAIN)
# ==========================
@app.post("/encode")
async def encode_face(image: UploadFile = File(...)):
    try:
        content = await image.read()
        pil_img = Image.open(io.BytesIO(content)).convert("RGB")
        cv2_img = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)

        quality = compute_quality_score(cv2_img)
        embedding = extract_face_embedding(cv2_img)

        if embedding is None:
            return JSONResponse(
                {"success": False, "message": "Wajah tidak terdeteksi."},
                status_code=200,
            )

        return {"success": True, "embedding": embedding, "quality_score": quality}

    except Exception as e:
        debug_print("Error /encode:", e)
        return JSONResponse(
            {"success": False, "message": f"Error server: {str(e)}"},
            status_code=500,
        )


# ==========================
# MAIN RUNNER
# ==========================
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
# ==========================
# ==========================
# /recognize (FINAL USE)
# ==========================
@app.post("/recognize")
async def recognize_face(image: UploadFile = File(...)):
    try:
        # === 1. Read Image ===
        content = await image.read()
        pil_img = Image.open(io.BytesIO(content)).convert("RGB")
        cv2_img = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)

        # === 2. Compute embedding ===
        probe_embedding = extract_face_embedding(cv2_img)
        if probe_embedding is None:
            return JSONResponse({"success": False, "message": "Wajah tidak terdeteksi."}, status_code=200)

        # === 3. Load database faces ===
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT user_id, face_encoding
            FROM face_data
            WHERE is_active = 1 AND face_encoding IS NOT NULL
        """)
        records = cursor.fetchall()
        cursor.close()
        conn.close()

        if not records:
            return JSONResponse({"success": False, "message": "Belum ada data wajah terdaftar."}, status_code=200)

        # === 4. Compare distances ===
        best_user = None
        best_distance = None

        for row in records:
            try:
                db_vec = json.loads(row["face_encoding"])
                dist = euclidean_distance(probe_embedding, db_vec)

                if best_distance is None or dist < best_distance:
                    best_distance = dist
                    best_user = row["user_id"]
            except:
                continue

        # === 5. Compute Confidence ===
        conf = distance_to_confidence(best_distance)

        response = {
            "success": True,
            "message": "Wajah diproses.",
            "data": {
                "user_id": best_user,
                "distance": best_distance,
                "confidence": conf
            }
        }
        return JSONResponse(response, status_code=200)

    except Exception as e:
        debug_print("Error /recognize:", e)
        return JSONResponse({"success": False, "message": str(e)}, status_code=500)
