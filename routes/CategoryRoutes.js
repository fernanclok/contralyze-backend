import express from "express";
import validateToken from "../middlewares/ValidateToken.js";
import { createCategory, getCategories } from "../controllers/CategoryController.js";
const router = express.Router();

router.post("/create", validateToken, createCategory);
router.get("/", validateToken, getCategories);

export default router;