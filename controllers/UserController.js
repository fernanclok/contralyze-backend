import { config as dotenvConfig } from "dotenv";
import { User } from "../models/User.js";
import jwt from "jsonwebtoken";
import bcrypt from "bcryptjs";


dotenvConfig();

export const loginUser = async (req, res) => {
  try {
    const email = req.body?.email;
    const password = req.body?.password;

    if (!email || !password) {
      const missingFields = [];
      if (!email) missingFields.push("email");
      if (!password) missingFields.push("password");

      return res.status(400).json({
        error: "Validation error",
        message: `Missing required fields: ${missingFields.join(", ")}`,
      });
    }

    const user = await User.findOne({ where: { email }, paranoid: false });

    if (!user) {
      return res.status(404).json({
        error: "Not found",
        message: "User not found",
      });
    }

    const validPassword = bcrypt.compare(password, user.password);

    if (!validPassword) {
      return res.status(401).json({
        error: "Unauthorized",
        message: "Invalid password",
      });
    }

    const accessToken = jwt.sign(
      { id: user.id, username: user.username },
      process.env.JWT_SECRET,
      {
        expiresIn: "1h",
      }
    );

    const refreshToken = jwt.sign(
      { id: user.id, username: user.username },
      process.env.JWT_REFRESH_SECRET,
      {
        expiresIn: "7d",
      }
    );

    res.cookie("refresh_token", refreshToken, {
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax", //strict
      maxAge: 1000 * 60 * 60 * 24 * 7, // 7 dias
    });

    res.cookie("access_token", accessToken, {
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax", //strict
      maxAge: 1000 * 60 * 60, // 1 dia
    });

    const { password: _, ...userWithoutPassword } = user.dataValues;

    return res.status(200).json({
      user: userWithoutPassword,
    });
  } catch (error) {
    console.log(error);
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};

export const registerUser = async (req, res) => {

  try {
    const first_name = req.body?.first_name;
    const last_name = req.body?.last_name;
    const email = req.body?.email;
    const password = req.body?.password;
    const role = req.body?.role;

    if (!first_name || !last_name || !email || !password || !role) {
      const missingFields = [];
      if (!first_name) missingFields.push("first_name");
      if (!last_name) missingFields.push("last_name");
      if (!email) missingFields.push("email");
      if (!password) missingFields.push("password");
      if (!role) missingFields.push("role");

      return res.status(400).json({
        error: "Validation error",
        message: `Missing required fields: ${missingFields.join(", ")}`,
      });
    }

    const hashedPassword = await bcrypt.hash(password, 10);

    const save = await User.create({
      first_name,
      last_name,
      email,
      password: hashedPassword,
      role,
    });

    const { password: _, ...userWithoutPassword } = save.dataValues;

    return res.status(201).send({
      user: userWithoutPassword,
    });
  } catch (error) {
    if (error.name === "SequelizeUniqueConstraintError") {
      return res.status(400).json({
        error: "Validation error",
        message: "Email or username already in use",
      });
    }
  }
  console.log(error);
  res.status(500).json({
    error: "Internal server error",
    message: "An error occurred while trying to create the user",
  });
};

export const logoutUser = (req, res) => {
  res.clearCookie("access_token");
  res.status(200).json({
    message: "User logged out successfully",
  });
};

export const tryAuth = (req, res) => {
  res.status(200).json({
    message: "this is a protected route",
    user: req.user,
  });
};

export const refreshAccessToken = async (req, res) => {
  try {
    // Obtener el refresh token de las cookies
    const refreshToken = req.cookies?.refresh_token;

    if (!refreshToken) {
      return res.status(401).json({
        error: "Unauthorized",
        message: "No refresh token provided",
      });
    }

    // Verificar el refresh token
    jwt.verify(refreshToken, process.env.JWT_REFRESH_SECRET, (err, user) => {
      if (err) {
        return res.status(403).json({
          error: "Forbidden",
          message: "Invalid or expired refresh token",
        });
      }

      // Generar un nuevo access token
      const accessToken = jwt.sign(
        { id: user.id, username: user.username },
        process.env.JWT_SECRET,
        {
          expiresIn: "1h",
        }
      );

      // Enviar el nuevo access token
      res.cookie("access_token", accessToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        sameSite: "strict",
        maxAge: 1000 * 60 * 60, // 1 dia
      });

      return res.status(200).json({
        access_token: accessToken,
      });
    });
  } catch (error) {
    console.log(error);
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};
