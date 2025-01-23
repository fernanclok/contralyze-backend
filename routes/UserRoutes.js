import express from "express";
import validateToken from "../middlewares/ValidateToken.js";
import { loginUser, registerUser, logoutUser, tryAuth, refreshAccessToken } from "../controllers/UserController.js";

const router = express.Router();

router.post("/login", loginUser);
router.post("/register", registerUser);
router.post('/try-auth', validateToken , tryAuth);
router.post("/logout", logoutUser);
router.post('/refresh-token', refreshAccessToken);

export default router;