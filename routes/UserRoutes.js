import express from "express";
import validateToken from "../middlewares/ValidateToken.js";
import { loginUser, registerUser, logoutUser, verifyAuth, refreshAccessToken } from "../controllers/UserController.js";

const router = express.Router();

router.post("/login", loginUser);
router.post("/register", registerUser);
router.get("/verify-auth", validateToken , verifyAuth);
router.post("/logout", logoutUser);
router.post("/refresh-token", refreshAccessToken);

export default router;