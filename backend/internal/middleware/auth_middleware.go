package middleware

import (
	"database/sql"
	"net/http"
	"strings"

	"backend/internal/model"
	"backend/pkg/utils"

	"github.com/gin-gonic/gin"
)

func AuthMiddleware(db *sql.DB) gin.HandlerFunc {
	return func(c *gin.Context) {
		authHeader := c.GetHeader("Authorization")
		if authHeader == "" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Authorization header required"})
			return
		}

		parts := strings.Split(authHeader, " ")
		if len(parts) != 2 || parts[0] != "Bearer" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Invalid token format"})
			return
		}

		tokenString := parts[1]

		// 1. Try to validate as JWT
		claims, err := utils.ValidateJWT(tokenString)
		if err != nil {
			// Fallback: Check if it's a legacy UUID token in the database
			var user model.User
			errDB := db.QueryRow(`
				SELECT u.id, u.role_id, r.name, u.username, u.full_name, IFNULL(u.is_profile_complete, 0)
				FROM users u
				JOIN roles r ON u.role_id = r.id
				WHERE u.token = ?`, tokenString).Scan(&user.ID, &user.RoleID, &user.RoleName, &user.Username, &user.FullName, &user.IsProfileComplete)
			
			if errDB != nil {
				c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Invalid or expired token"})
				return
			}
			c.Set("user", user)
			c.Next()
			return
		}

		// 2. JWT is valid. Fetch latest user details from DB.
		var user model.User
		err = db.QueryRow(`
			SELECT u.id, u.role_id, r.name, u.username, u.full_name, IFNULL(u.is_profile_complete, 0)
			FROM users u
			JOIN roles r ON u.role_id = r.id
			WHERE u.id = ?`, claims.UserID).Scan(&user.ID, &user.RoleID, &user.RoleName, &user.Username, &user.FullName, &user.IsProfileComplete)

		if err != nil {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "User not found"})
			return
		}

		c.Set("user", user)
		c.Next()
	}
}

// RequirePlatform enforces platform restrictions (web vs mobile)
func RequirePlatform() gin.HandlerFunc {
	return func(c *gin.Context) {
		// Expect client to send X-Client-Type header (e.g. "web" or "mobile")
		clientType := c.GetHeader("X-Client-Type")
		if clientType == "" {
			clientType = "web" // fallback to web
		}

		userObj, exists := c.Get("user")
		if !exists {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Unauthorized"})
			return
		}

		user := userObj.(model.User)

		// Admins and Doctors are strictly Web-only
		if (user.RoleName == "Admin" || user.RoleName == "Doctor") && clientType == "mobile" {
			c.AbortWithStatusJSON(http.StatusForbidden, gin.H{"error": "Akses ditolak: Platform mobile tidak didukung untuk role Anda."})
			return
		}

		c.Next()
	}
}

func RoleMiddleware(roles ...string) gin.HandlerFunc {
	return func(c *gin.Context) {
		userObj, exists := c.Get("user")
		if !exists {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"error": "Unauthorized"})
			return
		}

		user := userObj.(model.User)
		allowed := false
		for _, role := range roles {
			if user.RoleName == role {
				allowed = true
				break
			}
		}

		if !allowed {
			c.AbortWithStatusJSON(http.StatusForbidden, gin.H{"error": "Forbidden access"})
			return
		}

		c.Next()
	}
}
