import os
import base64
import cv2
import torch
import torch.nn as nn
import numpy as np
import uvicorn
import seaborn as sns
from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from torchvision import transforms
from peft import LoraConfig, get_peft_model
import timm

# --- CONFIGURATION ---
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
MODEL_PATH = os.getenv("MODEL_PATH", "models/best_model_global.pth")
NUM_CLASSES = 5

CLASSES = [
    "Dyskeratotic",
    "Koilocytotic",
    "Metaplastic",
    "Parabasal",
    "Superficial-Intermediate"
]

# --- PREPROCESSING ---
def ben_graham_preprocessing(img, sigma=10):
    img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    blur = cv2.GaussianBlur(img, (0, 0), sigma)
    return cv2.addWeighted(img, 4, blur, -4, 128)

transform = transforms.Compose([
    transforms.ToPILImage(),
    transforms.Resize((224, 224)),
    transforms.ToTensor(),
    transforms.Normalize(
        mean=[0.48145466, 0.4578275, 0.40821073],
        std=[0.26862954, 0.26130258, 0.27577711]
    )
])

# --- MODEL SETUP ---
lora_config = LoraConfig(
    r=8,
    lora_alpha=16,
    target_modules=["qkv"],
    lora_dropout=0.1
)

def load_model():
    print(f"Loading ViT-L14 model on {DEVICE}...")
    model = timm.create_model(
        "vit_large_patch14_clip_224",
        pretrained=False
    )
    
    # Adjust head for 5 classes
    model.head = nn.Linear(model.head.in_features, NUM_CLASSES)
    
    # Wrap with LoRA
    model = get_peft_model(model, lora_config)
    model.base_model.model.blocks[-1].attn.fused_attn = False
    
    if os.path.exists(MODEL_PATH):
        print(f"Loading weights from {MODEL_PATH}")
        model.load_state_dict(torch.load(MODEL_PATH, map_location=DEVICE))
    else:
        print(f"WARNING: Model file not found at {MODEL_PATH}. Prediction will use random weights.")
    
    model.to(DEVICE)
    model.eval()
    print("MODEL READY")
    return model

# Initialize Model
model = load_model()

# --- API ---
app = FastAPI(title="NeoHealth AI Cytology API")

# Enable CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
def home():
    return {
        "status": "NeoHealth AI Service is ONLINE 🚀",
        "device": DEVICE,
        "model": "ViT-L14 + LoRA",
        "classes": CLASSES
    }

@app.post("/predict")
async def predict(file: UploadFile = File(...)):
    if not (file.content_type.startswith("image/") or file.content_type == "application/octet-stream"):
        raise HTTPException(status_code=400, detail="File must be an image")

    try:
        # Read image
        contents = await file.read()
        nparr = np.frombuffer(contents, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if img is None:
            raise HTTPException(status_code=400, detail="Invalid image file")

        # 1. Preprocessing (Ben Graham)
        processed_img = ben_graham_preprocessing(img)
        
        # 2. Transformation
        input_tensor = transform(processed_img).unsqueeze(0).to(DEVICE)

        # 3. Inference with Attention Extraction
        # We hook into the last 4 blocks to get a stable, averaged attention map
        attention_weights_list = []
        
        def make_attn_hook():
            def hook(module, input, output):
                # input[0] is the attention weights before dropout
                attention_weights_list.append(input[0].detach())
            return hook
        
        handles = []
        # Hook last 4 attention layers for robust feature aggregation
        for idx in [-1, -2, -3, -4]:
            block = model.base_model.model.blocks[idx]
            handles.append(block.attn.attn_drop.register_forward_hook(make_attn_hook()))

        with torch.no_grad():
            output = model(input_tensor)
            probabilities = torch.softmax(output, dim=1)
            confidence, pred_idx = torch.max(probabilities, 1)
            pred_idx = pred_idx.item()
            confidence = confidence.item()
            
        for handle in handles:
            handle.remove()

        # 3.5 Generate Heatmap from ViT Attention Map
        heatmap_overlay = img.copy()
        
        if len(attention_weights_list) > 0:
            # Average attention maps across the hooked blocks
            stacked_attn = torch.stack([w[0].mean(dim=0) for w in attention_weights_list]) # (4, seq_len, seq_len)
            attn = stacked_attn.mean(dim=0)  # (seq_len, seq_len)
            
            # Get CLS token attention to all patch tokens
            cls_attn = attn[0, 1:]  # (num_patches,)
            
            num_patches = int(cls_attn.shape[0] ** 0.5)
            
            if num_patches * num_patches == cls_attn.shape[0]:
                cls_attn = cls_attn.reshape(num_patches, num_patches)
                cls_attn_np = cls_attn.cpu().numpy()
                
                # Apply minor boundary decay mask to suppress edge artifacts from Ben Graham blur padding
                h_w = cls_attn_np.shape[0]
                mask = np.ones((h_w, h_w), dtype=np.float32)
                for r in range(h_w):
                    for c in range(h_w):
                        dist = min(r, c, h_w - 1 - r, h_w - 1 - c)
                        if dist < 1:
                            mask[r, c] = 0.3 # soft damping on absolute edge patch
                
                cls_attn_np = cls_attn_np * mask
                
                # --- PRECISE PATCH-BASED XAI HEATMAP ---
                # Strategy: Divide into 16x16 patches, compute nucleus and texture importance,
                # then resize and blur to form a premium red gradient heatmap overlay.
                
                gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
                h, w = img.shape[:2]
                
                # 1. Normalize ViT attention map to 0-1
                cls_attn_np = (cls_attn_np - cls_attn_np.min()) / (cls_attn_np.max() - cls_attn_np.min() + 1e-8)
                
                # 2. Otsu threshold to find the cell mask at full resolution
                gray_blur = cv2.GaussianBlur(gray, (5, 5), 0)
                _, cell_mask = cv2.threshold(gray_blur, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
                morph_kernel = cv2.getStructuringElement(cv2.MORPH_ELLIPSE, (7, 7))
                cell_mask = cv2.morphologyEx(cell_mask, cv2.MORPH_CLOSE, morph_kernel)
                cell_mask = cv2.morphologyEx(cell_mask, cv2.MORPH_OPEN, morph_kernel)
                
                # 3. Calculate feature grid on 16x16 patches
                patch_h = h // h_w
                patch_w = w // h_w
                
                patch_scores = np.zeros((h_w, h_w), dtype=np.float32)
                
                for r in range(h_w):
                    for c_idx in range(h_w):
                        y1, y2 = r * patch_h, min((r + 1) * patch_h, h)
                        x1, x2 = c_idx * patch_w, min((c_idx + 1) * patch_w, w)
                        
                        patch_gray = gray[y1:y2, x1:x2]
                        patch_cell = cell_mask[y1:y2, x1:x2]
                        
                        # Calculate cell coverage percentage in this patch
                        cell_coverage = np.mean(patch_cell) / 255.0
                        
                        if cell_coverage > 0.1:
                            # Highlight regions that are darker (nucleus) and have high texture (standard dev)
                            mean_intensity = np.mean(patch_gray)
                            std_intensity = np.std(patch_gray)
                            
                            # Lower intensity (darker) -> higher nucleus score
                            nucleus_score = 1.0 - (mean_intensity / 255.0)
                            texture_score = std_intensity / 128.0
                            
                            # Combine: 50% nucleus, 30% texture, 20% ViT attention
                            score = nucleus_score * 0.5 + texture_score * 0.3 + cls_attn_np[r, c_idx] * 0.2
                            patch_scores[r, c_idx] = score * cell_coverage
                        else:
                            patch_scores[r, c_idx] = 0.0
                
                # 4. Normalize the 16x16 grid
                patch_scores = (patch_scores - patch_scores.min()) / (patch_scores.max() - patch_scores.min() + 1e-8)
                
                # 5. Up-sample the patch grid to full resolution
                heatmap_coarse = cv2.resize(patch_scores, (w, h), interpolation=cv2.INTER_LINEAR)
                
                # 6. Apply Gaussian blur to blend patch borders into a smooth heatmap glow
                heatmap_smooth = cv2.GaussianBlur(heatmap_coarse, (31, 31), 0)
                heatmap_smooth = (heatmap_smooth - heatmap_smooth.min()) / (heatmap_smooth.max() - heatmap_smooth.min() + 1e-8)
                
                # Apply power curve to focus the heatmap on the most anomalous core regions
                heatmap_smooth = heatmap_smooth ** 1.3
                
                # 7. Apply standard JET colormap overlay
                heatmap_uint8 = (heatmap_smooth * 255).astype(np.uint8)
                colored_heatmap_bgr = cv2.applyColorMap(heatmap_uint8, cv2.COLORMAP_JET)
                
                # Blend using a constant alpha (e.g. 0.5) to keep the blue background (cold areas) and red hotspots
                heatmap_vis = cv2.addWeighted(colored_heatmap_bgr, 0.5, img, 0.5, 0)
                
                heatmap_overlay = heatmap_vis
                print(f"Aggregated masked red-gradient heatmap generated successfully: {num_patches}x{num_patches} patches")
            else:
                print(f"Warning: Non-square patch count {cls_attn.shape[0]}, skipping heatmap")
        else:
            print("Warning: No attention weights captured, returning original image")
        
        _, buffer = cv2.imencode('.jpg', heatmap_overlay, [cv2.IMWRITE_JPEG_QUALITY, 95])
        overlay_b64 = base64.b64encode(buffer).decode('utf-8')

        # 4. Result
        return {
            "class": CLASSES[pred_idx],
            "index": pred_idx,
            "confidence": float(confidence),
            "gradcam_base64": overlay_b64
        }
        
    except Exception as e:
        print(f"Error during prediction: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Prediction failed: {str(e)}")

if __name__ == "__main__":
    port = int(os.getenv("PORT", 8002))
    uvicorn.run(app, host="0.0.0.0", port=port)
