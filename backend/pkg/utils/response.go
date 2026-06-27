package utils

import (
	"github.com/gin-gonic/gin"
)

// Response Structs
type SuccessRes struct {
	Success bool        `json:"success"`
	Message string      `json:"message"`
	Data    interface{} `json:"data,omitempty"`
}

type ErrorRes struct {
	Success bool        `json:"success"`
	Message string      `json:"message"`
	Errors  interface{} `json:"errors,omitempty"`
}

type PaginationMeta struct {
	Page  int `json:"page"`
	Limit int `json:"limit"`
	Total int `json:"total"`
}

type PaginationRes struct {
	Success bool           `json:"success"`
	Message string         `json:"message"`
	Data    interface{}    `json:"data"`
	Meta    PaginationMeta `json:"meta"`
}

// SuccessResponse sends a standardized success JSON response
func SuccessResponse(c *gin.Context, statusCode int, message string, data interface{}) {
	c.JSON(statusCode, SuccessRes{
		Success: true,
		Message: message,
		Data:    data,
	})
}

// ErrorResponse sends a standardized error JSON response
func ErrorResponse(c *gin.Context, statusCode int, message string, errors interface{}) {
	c.JSON(statusCode, ErrorRes{
		Success: false,
		Message: message,
		Errors:  errors,
	})
}

// PaginatedResponse sends a standardized paginated JSON response
func PaginatedResponse(c *gin.Context, statusCode int, message string, data interface{}, page, limit, total int) {
	c.JSON(statusCode, PaginationRes{
		Success: true,
		Message: message,
		Data:    data,
		Meta: PaginationMeta{
			Page:  page,
			Limit: limit,
			Total: total,
		},
	})
}
