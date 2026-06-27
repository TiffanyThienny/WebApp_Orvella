# VITL14+LoRA Training Notebook logic (Transcribed from provided code)
# SIPAKMED Dataset Classification

import os
import cv2
import torch
import timm
import numpy as np
import torch.nn as nn
import torch.optim as optim
from tqdm import tqdm
from sklearn.model_selection import KFold
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from torchvision import transforms
from torch.utils.data import Dataset, DataLoader, Subset
from peft import LoraConfig, get_peft_model

# CONFIG
DATASET_PATH = "path/to/dataset"
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
BATCH_SIZE = 8
EPOCHS = 50
NUM_CLASSES = 5

def ben_graham_preprocessing(img, sigma=10):
    img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
    blur = cv2.GaussianBlur(img, (0,0), sigma)
    processed = cv2.addWeighted(img, 4, blur, -4, 128)
    return processed

transform = transforms.Compose([
    transforms.ToPILImage(),
    transforms.Resize((224,224)),
    transforms.RandomHorizontalFlip(),
    transforms.RandomRotation(15),
    transforms.ToTensor(),
    transforms.Normalize(
        mean=[0.48145466,0.4578275,0.40821073],
        std=[0.26862954,0.26130258,0.27577711]
    )
])

class SIPAKMEDDataset(Dataset):
    def __init__(self, root_dir, transform=None):
        self.images = []
        self.labels = []
        self.transform = transform
        classes = ["im_Dyskeratotic", "im_Koilocytotic", "im_Metaplastic", "im_Parabasal", "im_Superficial-Intermediate"]
        for label, cls in enumerate(classes):
            path = os.path.join(root_dir, cls, cls, "CROPPED")
            for img_name in os.listdir(path):
                if img_name.endswith(".bmp"):
                    self.images.append(os.path.join(path, img_name))
                    self.labels.append(label)

    def __len__(self): return len(self.images)
    def __getitem__(self, idx):
        img = cv2.imread(self.images[idx])
        img = ben_graham_preprocessing(img)
        if self.transform: img = self.transform(img)
        return img, self.labels[idx]

# LoRA Setup
lora_config = LoraConfig(r=8, lora_alpha=16, target_modules=["qkv"], lora_dropout=0.1)

# Training logic with K-Fold is implemented in the provided snippet...
