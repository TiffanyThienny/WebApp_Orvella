import torch
import timm
from peft import LoraConfig, get_peft_model
import torch.nn as nn

# Model configuration
lora_config = LoraConfig(
    r=8,
    lora_alpha=16,
    target_modules=["qkv"],
    lora_dropout=0.1
)

model = timm.create_model("vit_large_patch14_clip_224", pretrained=False)
model.head = nn.Linear(model.head.in_features, 5)
model = get_peft_model(model, lora_config)

# Disable fused attention on the last block
last_block = model.base_model.model.blocks[-1]
last_block.attn.fused_attn = False

print("fused_attn disabled on last block:", last_block.attn.fused_attn)

# Test forward pass with hook
input_tensor = torch.randn(1, 3, 224, 224)
attention_weights = {}

def attn_hook(module, input, output):
    print("Hook triggered!")
    # In manual implementation: attn = self.attn_drop(attn)
    # The input to attn_drop is the attention map itself!
    attention_weights['attn'] = input[0].detach()

# Register hook on the last block's attn_drop
last_block.attn.attn_drop.register_forward_hook(attn_hook)

# Run model
try:
    with torch.no_grad():
        out = model(input_tensor)
    print("Forward completed successfully!")
except Exception as e:
    print(f"Error during forward: {e}")

print(f"Captured keys: {list(attention_weights.keys())}")
if 'attn' in attention_weights:
    print("Shape of captured attention weights:", attention_weights['attn'].shape)
