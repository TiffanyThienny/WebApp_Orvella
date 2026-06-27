import requests
import json

# Test the AI service directly
url = "http://localhost:8002/predict"

# Use one of the existing scan images
image_path = r"c:\FinproPPT\Orvella_UTS_PPT-main\backend\uploads\ct_scans\1781170426255702000_c19a8dc2-1aeb-4c5f-8646-2d8ad0a902ae.jpeg"

with open(image_path, "rb") as f:
    files = {"file": ("test.jpeg", f, "image/jpeg")}
    resp = requests.post(url, files=files, timeout=60)

data = resp.json()
print(f"Status: {resp.status_code}")
print(f"Class: {data.get('class')}")
print(f"Confidence: {data.get('confidence')}")
print(f"Has gradcam_base64: {bool(data.get('gradcam_base64'))}")
if data.get('gradcam_base64'):
    print(f"gradcam_base64 length: {len(data['gradcam_base64'])}")
else:
    print("NO GRADCAM BASE64 RETURNED!")
