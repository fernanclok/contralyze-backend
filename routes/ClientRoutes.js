import express from "express";
import validateToken from "../middlewares/ValidateToken.js";
import { createClient, getClients, getClient, updateClient } from "../controllers/ClientController.js";

const router = express.Router();

router.post("/create", validateToken, createClient);
router.get("/", validateToken, getClients);
router.get("/:id", validateToken, getClient);
router.put("/update/:id", validateToken, updateClient);

export default router;