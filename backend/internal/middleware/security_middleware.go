package middleware

import (
	"fmt"
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
	"golang.org/x/time/rate"
)

// cloudflare-like Security Middleware
var (
	visitors = make(map[string]*rate.Limiter)
	mu       sync.Mutex
)

// getLimiter retrieves or creates a rate limiter for a specific IP.
func getLimiter(ip string) *rate.Limiter {
	mu.Lock()
	defer mu.Unlock()

	limiter, exists := visitors[ip]
	if !exists {
		// 10 requests per second, maximum burst of 20 - simulating standard WAF protection
		limiter = rate.NewLimiter(rate.Limit(10), 20)
		visitors[ip] = limiter
	}

	return limiter
}

// CloudflareSecurity simulates basic Cloudflare headers and rate-limiting rules.
func CloudflareSecurity() gin.HandlerFunc {
	return func(c *gin.Context) {
		clientIP := c.ClientIP()
		limiter := getLimiter(clientIP)

		// Check rate limiting
		if !limiter.Allow() {
			// Simulating Cloudflare Error 429 Too Many Requests
			c.AbortWithStatusJSON(http.StatusTooManyRequests, gin.H{
				"error":       "Rate limit exceeded.",
				"cf-ray":      fmt.Sprintf("%d-LAX", time.Now().UnixNano()),
				"description": "Please slow down your requests. Secured by Cloudflare Simulation.",
			})
			return
		}

		// Inject simulated CF security headers on response
		c.Writer.Header().Set("X-Content-Type-Options", "nosniff")
		c.Writer.Header().Set("X-Frame-Options", "DENY")
		c.Writer.Header().Set("X-XSS-Protection", "1; mode=block")
		c.Writer.Header().Set("Server", "cloudflare")

		c.Next()
	}
}

// StrictRateLimit simulates strict Cloudflare login/upload protections (e.g. 2 req/sec)
func StrictRateLimit() gin.HandlerFunc {
	var strictMu sync.Mutex
	strictVisitors := make(map[string]*rate.Limiter)

	return func(c *gin.Context) {
		clientIP := c.ClientIP()

		strictMu.Lock()
		limiter, exists := strictVisitors[clientIP]
		if !exists {
			limiter = rate.NewLimiter(rate.Limit(2), 5)
			strictVisitors[clientIP] = limiter
		}
		strictMu.Unlock()

		if !limiter.Allow() {
			c.AbortWithStatusJSON(http.StatusTooManyRequests, gin.H{
				"error":  "Strict rate limit enforced for sensitive route.",
				"cf-ray": fmt.Sprintf("strict-%d", time.Now().UnixNano()),
			})
			return
		}

		c.Next()
	}
}
