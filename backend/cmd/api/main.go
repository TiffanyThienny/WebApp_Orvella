package main

import (
	"log"
	"net/http"
	"backend/config"
	"backend/internal/handler"
	"backend/internal/middleware"
	"backend/internal/repository"
	"backend/internal/service"
	"backend/pkg/storage"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
)

func main() {
	db := config.InitDB()
	defer db.Close()

	// Initialize Repositories
	userRepo := repository.NewUserRepository(db)
	scanRepo := repository.NewScanRepository(db)
	recordRepo := repository.NewRecordRepository(db)
	appointmentRepo := repository.NewAppointmentRepository(db)
	scheduleRepo := repository.NewScheduleRepository(db)

	// Initialize Services
	authService := service.NewAuthService(userRepo)
	scanService := service.NewScanService(scanRepo, userRepo)
	recordService := service.NewRecordService(recordRepo, userRepo)

	// Initialize Handlers
	s3Storage := storage.NewMockS3Storage()
	authHandler := handler.NewAuthHandler(authService)
	scanHandler := handler.NewScanHandler(scanService, s3Storage)
	recordHandler := handler.NewRecordHandler(recordService)
	userHandler := handler.NewUserHandler(userRepo, scanRepo, recordRepo)
	scheduleHandler := handler.NewScheduleHandler(scheduleRepo)
	appointmentHandler := handler.NewAppointmentHandler(appointmentRepo, userRepo, scheduleRepo)
	exportHandler := handler.NewExportHandler(userRepo, scanRepo, recordRepo)
	configRepo := repository.NewConfigRepository(db)
	configHandler := handler.NewConfigHandler(configRepo)

	r := gin.Default()

	// CORS config
	corsConfig := cors.DefaultConfig()
	corsConfig.AllowAllOrigins = true
	corsConfig.AllowHeaders = []string{"Origin", "Content-Length", "Content-Type", "Authorization", "X-Client-Type"}
	r.Use(cors.New(corsConfig))

	// Cloudflare Simulation global middleware
	r.Use(middleware.CloudflareSecurity())

	// Static route for uploads
	r.Static("/uploads", "./uploads")

	// Public routes
	r.GET("/", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"message": "Orvella Backend is running", "status": "secure"})
	})
	r.GET("/configs", configHandler.GetConfigs)
	r.POST("/login", middleware.StrictRateLimit(), authHandler.Login)
	r.POST("/forgot-password", authHandler.ForgotPassword)
	// Public registration is now disabled, restricted to Admin only below

	// Protected routes
	api := r.Group("/")
	api.Use(middleware.AuthMiddleware(db))
	api.Use(middleware.RequirePlatform())
	{
		// Site Configuration
		api.POST("/configs", middleware.RoleMiddleware("Admin"), configHandler.UpdateConfig)
		// Auth user & Profile
		api.GET("/me", authHandler.Me)
		api.POST("/logout", authHandler.Logout)
		api.POST("/register", middleware.RoleMiddleware("Admin"), authHandler.Register)
		api.GET("/profile", userHandler.GetProfile)
		api.PUT("/profile", userHandler.UpdateProfile)
		api.PUT("/profile/complete", userHandler.CompleteProfile)

		// Users & Analytics
		api.GET("/users", middleware.RoleMiddleware("Admin"), authHandler.GetUsers)
		api.GET("/analytics/admin", middleware.RoleMiddleware("Admin"), userHandler.GetAdminAnalytics)
		api.PUT("/users/:id", middleware.RoleMiddleware("Admin"), userHandler.UpdateUser)
		api.DELETE("/users/:id", middleware.RoleMiddleware("Admin"), userHandler.DeleteUser)
		api.GET("/doctors", userHandler.GetDoctors)

		api.GET("/analytics/doctor/stats", middleware.RoleMiddleware("Doctor"), userHandler.GetDoctorStats)
		// Patients (Doctor / Admin / Medical Record)
		api.GET("/patients", middleware.RoleMiddleware("Doctor", "Admin", "Medical Record"), userHandler.GetPatients)
		api.GET("/patients/:id", middleware.RoleMiddleware("Doctor", "Admin", "Medical Record"), userHandler.GetPatientDetail)
		api.PUT("/patients/:id", middleware.RoleMiddleware("Doctor", "Admin", "Medical Record"), userHandler.UpdatePatientDetail)

		// Scans
		api.GET("/scans", scanHandler.GetScans)

		// Upload scan: Admin or Medical Record
		api.POST("/scans", middleware.StrictRateLimit(), middleware.RoleMiddleware("Admin", "Medical Record"), scanHandler.UploadScan)
		api.POST("/scans/:id/analyze", middleware.RoleMiddleware("Admin", "Medical Record", "Doctor"), scanHandler.AnalyzeScan)
		api.POST("/scans/:id/assign-doctor", middleware.RoleMiddleware("Admin", "Medical Record"), scanHandler.AssignDoctor)

		// Diagnosis
		api.POST("/diagnosis", middleware.RoleMiddleware("Doctor"), scanHandler.SubmitDiagnosis)
		api.PUT("/diagnosis/:id/approve", middleware.RoleMiddleware("Doctor", "Admin"), scanHandler.ApproveDiagnosis)
		api.PUT("/diagnosis/:id/reject", middleware.RoleMiddleware("Doctor", "Admin"), scanHandler.RejectDiagnosis)


		// Health Records
		api.GET("/health-records/graph", middleware.RoleMiddleware("Patient", "Doctor", "Admin"), recordHandler.GetGraphData)
		api.POST("/health-records", middleware.RoleMiddleware("Doctor", "Admin", "Medical Record"), recordHandler.CreateRecord)
		api.PUT("/health-records/:id", middleware.RoleMiddleware("Doctor", "Admin"), recordHandler.UpdateRecord)
		api.DELETE("/health-records/:id", middleware.RoleMiddleware("Doctor", "Admin"), recordHandler.DeleteRecord)

		// Appointments
		api.POST("/appointments", middleware.RoleMiddleware("Patient"), appointmentHandler.Create)
		api.GET("/appointments", middleware.RoleMiddleware("Patient", "Doctor"), appointmentHandler.Get)
		api.PUT("/appointments/:id/status", middleware.RoleMiddleware("Doctor"), appointmentHandler.UpdateStatus)
		api.PUT("/appointments/:id/cancel", middleware.RoleMiddleware("Patient"), appointmentHandler.Cancel)

		// Schedules
		api.POST("/admin/schedules", middleware.RoleMiddleware("Admin"), scheduleHandler.AssignSchedule)
		api.PUT("/admin/schedules/:id", middleware.RoleMiddleware("Admin"), scheduleHandler.UpdateSchedule)
		api.GET("/schedules", scheduleHandler.GetAllSchedules)
		api.GET("/schedules/:doctor_id", scheduleHandler.GetDoctorSchedules)
		api.DELETE("/admin/schedules/:id", middleware.RoleMiddleware("Admin"), scheduleHandler.DeleteSchedule)

		// Exports
		api.GET("/export/scans", middleware.RoleMiddleware("Admin", "Doctor"), exportHandler.ExportScans)
		api.GET("/export/patients", middleware.RoleMiddleware("Admin", "Doctor"), exportHandler.ExportPatients)
		api.GET("/export/users", middleware.RoleMiddleware("Admin"), exportHandler.ExportUsers)
		api.GET("/export/patient/:id", middleware.RoleMiddleware("Admin", "Doctor"), exportHandler.ExportSinglePatient)
		api.GET("/export/doctor/patients", middleware.RoleMiddleware("Doctor"), exportHandler.ExportDoctorPatients)
	}

	log.Println("Server starting on :8080")
	if err := r.Run(":8080"); err != nil {
		log.Fatal(err)
	}
}
