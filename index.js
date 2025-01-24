import express from "express";
import cookieParser from "cookie-parser";
import dotenv from "dotenv";
import { sequelize } from "./connection.js";
import models from "./models/index.js";

import userRoutes from "./routes/UserRoutes.js";
import clientRoutes from "./routes/ClientRoutes.js";
import categoryRoutes from "./routes/CategoryRoutes.js";

dotenv.config();

const PORT = process.env.PORT || 3000;

const app = express();
app.use (express.json());
app.use(cookieParser());

app.use("/user", userRoutes);
app.use("/client", clientRoutes);
app.use("/category", categoryRoutes);

sequelize
.authenticate()
.then(() => {
    console.log("Connection has been established successfully.");
    console.log(sequelize.models);
    // force: true will drop the table if it already exists (only in dev pls)
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