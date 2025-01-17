import express from "express";
import { PORT } from "./config.js";

import userRoutes from "./routes/UserRoutes.js";

const app = express();

app.use("/user", userRoutes);

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
