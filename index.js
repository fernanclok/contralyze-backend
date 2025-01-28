import express from "express";
import cors from "cors";
import cookieParser from "cookie-parser";
import dotenv from "dotenv";
import { sequelize } from "./connection.js";

import userRoutes from "./routes/UserRoutes.js";

dotenv.config();

const PORT = process.env.PORT || 3000;

const corsOptions = {
    origin: 'http://localhost:8081', // Reemplaza con el origen de tu frontend
    credentials: true, // Permitir cookies
};

const app = express();
app.use(express.json());
app.use(cookieParser());
app.use(cors(corsOptions)); // O permite un origen específico

app.use("/user", userRoutes);

sequelize
    .authenticate()
    .then(() => {
        console.log("Connection has been established successfully.");
        return sequelize.sync();
    })
    .then(() => {
        app.listen(PORT, () => {
            console.log(`Server is running on port ${PORT}`);
        });
    })
    .catch((error) => {
        console.error("Unable to connect to the database:", error);
    });