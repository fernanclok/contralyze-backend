import { Category } from "../models/Category.js";

export const createCategory = async (req, res) => {
  try {
    const name = req.body?.name;
    const type = req.body?.type;

    if (!name || !type) {
      return res.status(400).json({
        error: "Validation error",
        message: "Missing required fields: name",
      });
    }

    const category = await Category.create({
      name,
      type,
    });

    return res.status(201).send({
        category: category,
    });
  } catch (error) {
    return res.status(500).json({
      error: "Server error",
      message: "An error occurred while creating the category",
    });
  }
};

export const getCategories = async (req, res) => {
  try {
    const categories = await Category.findAll();

    return res.status(200).send({
      categories,
    });
  } catch (error) {
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};
