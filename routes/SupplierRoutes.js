import express from "express";
import validateToken from "../middlewares/ValidateToken.js";
import { createSupplier, getSuppliers, getSupplier, updateSupplier  } from "../controllers/SupplierController.js";

const router = express.Router();

router.post("/create", validateToken, createSupplier);
router.get("/", validateToken, getSuppliers);
router.get("/:id", validateToken, getSupplier);
router.put("/update/:id", validateToken, updateSupplier);

export default router;