import jwt from "jsonwebtoken";
import { User } from "../models/models.js";

const validateToken = async (req, res, next) => {
    const token = req.cookies.access_token;
    
    if (!token) {
        return res.status(401).json({
        error: "Unauthorized",
        message: "No token provided",
        });
    }
    
    try {
        const decoded = jwt.verify(token, process.env.JWT_SECRET);
        const user = await User.findByPk(decoded.id);
    
        if (!user) {
        return res.status(404).json({
            error: "Not found",
            message: "User not found",
        });
        }
    
        req.user = user;
        next();
    } catch (error) {
        return res.status(401).json({
        error: "Unauthorized",
        message: "Invalid token",
        });
    }
};

export default validateToken;