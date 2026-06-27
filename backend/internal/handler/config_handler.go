package handler

import (
	"net/http"
	"backend/internal/repository"
	"backend/pkg/utils"
	"github.com/gin-gonic/gin"
)

type ConfigHandler struct {
	Repo *repository.ConfigRepository
}

func NewConfigHandler(r *repository.ConfigRepository) *ConfigHandler {
	return &ConfigHandler{Repo: r}
}

func (h *ConfigHandler) GetConfigs(c *gin.Context) {
	configs, err := h.Repo.GetConfigs()
	if err != nil {
		utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to fetch configurations", err.Error())
		return
	}
	
	// Convert to map for easier frontend consumption
	configMap := make(map[string]string)
	for _, conf := range configs {
		configMap[conf.ConfigKey] = conf.ConfigValue
	}
	
	utils.SuccessResponse(c, http.StatusOK, "Configurations fetched successfully", configMap)
}

func (h *ConfigHandler) UpdateConfig(c *gin.Context) {
	var req struct {
		Configs map[string]string `json:"configs"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request body", err.Error())
		return
	}

	for key, value := range req.Configs {
		if err := h.Repo.UpdateConfig(key, value); err != nil {
			utils.ErrorResponse(c, http.StatusInternalServerError, "Failed to update " + key, err.Error())
			return
		}
	}

	utils.SuccessResponse(c, http.StatusOK, "Configurations updated successfully", nil)
}
