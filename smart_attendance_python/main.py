import os
import io
import json
from typing import Optional, List
from datetime import datetime

import numpy as np
import cv2
from PIL import Image

from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from dotenv import load_dotenv
import mysql.connector

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
    return mysql.connector.connect(**DB_CONFIG)

# ==========================
# FACE UTILS
# ==========================
CASCADE = cv2.CascadeClassifier(
    cv2.data.haarcascades + "haarcascade_frontalface_default.xml"
)

def extract_embedding(cv2_img):
    gray = cv2.cvtColor(cv2_img, cv2.COLOR_BGR2GRAY)

    # Tambahan: tingkatkan kontras (PENTING untuk kondisi agak gelap)
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



def euclidean(a, b) -> float:
    return float(np.linalg.norm(np.array(a) - np.array(b)))

def confidence(dist):
    return max(0.0, min(1.0, 1.0 - (dist / FACE_DISTANCE_THRESHOLD)))

# ==========================
# HEALTH
# ==========================
@app.get("/health")
def health():
    return {"status": "ok"}

# ==========================
# ENCODE
# ==========================
@app.post("/encode")
async def encode(image: UploadFile = File(...)):
    content = await image.read()
    img = Image.open(io.BytesIO(content)).convert("RGB")
    cv2_img = cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)

    embedding = extract_embedding(cv2_img)
    if embedding is None:
        return {"success": False, "message": "Wajah tidak terdeteksi."}

    return {
        "success": True,
        "embedding": embedding,
        "quality_score": 1.0
    }

# ==========================
# RECOGNIZE
# ==========================
@app.post("/recognize")
async def recognize(image: UploadFile = File(...)):
    content = await image.read()
    img = Image.open(io.BytesIO(content)).convert("RGB")
    cv2_img = cv2.cvtColor(np.array(img), cv2.COLOR_RGB2BGR)

    probe = extract_embedding(cv2_img)
    if probe is None:
        print("❌ GAGAL: Wajah tidak terdeteksi di gambar")
        return {"success": False, "message": "Wajah tidak terdeteksi."}

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

    print(f"📊 Jumlah wajah terdaftar: {len(rows)}")

    if len(rows) == 0:
        print("⚠️ TIDAK ADA wajah terdaftar di database!")
        return {
            "success": False,
            "message": "Tidak ada wajah terdaftar di database"
        }

    best_user = None
    best_dist = None

    for r in rows:
        vec = json.loads(r["face_encoding"])
        d = euclidean(probe, vec)
        print(f"   User {r['user_id']}: distance = {d:.2f}")
        if best_dist is None or d < best_dist:
            best_dist = d
            best_user = r["user_id"]

    print(f"🎯 Best: User {best_user}, dist={best_dist:.2f}, threshold={FACE_DISTANCE_THRESHOLD}")

    if best_dist is None or best_dist > FACE_DISTANCE_THRESHOLD:
        return {
            "success": False,
            "message": f"Wajah tidak dikenali (distance: {best_dist:.2f})",
            "data": {
                "distance": best_dist,
                "threshold": FACE_DISTANCE_THRESHOLD
            }
        }

    return {
        "success": True,
        "data": {
            "user_id": best_user,
            "distance": best_dist,
            "confidence": confidence(best_dist)
        }
    }

# ==========================
# RUN
# ==========================
if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
