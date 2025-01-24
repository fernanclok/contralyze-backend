import { DataTypes } from "sequelize";
import { sequelize } from "../connection.js";
import { User } from "../models/User.js";
import { Category } from "../models/Category.js";

const Budget = sequelize.define(
  "Budget",
  {
    id: {
      type: DataTypes.INTEGER,
      autoIncrement: true,
      primaryKey: true,
    },
    description: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    max_amount: {
      type: DataTypes.DECIMAL,
      allowNull: false,
    },
    start_date: {
      type: DataTypes.DATE,
      allowNull: false,
    },
    end_date: {
      type: DataTypes.DATE,
      allowNull: false,
    },
    status: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    category_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    createdAt: {
      type: DataTypes.DATE,
      defaultValue: DataTypes.NOW,
    },
    updatedAt: {
      type: DataTypes.DATE,
      defaultValue: DataTypes.NOW,
    },
  },
  { tableName: "budgets" }
);

// user relationship
User.hasMany(Budget, { foreignKey: "user_id", as: "budgets" });

Budget.belongsTo(User, { foreignKey: "user_id", as: "user" });

// category relationship
Category.hasMany(Budget, { foreignKey: "category_id", as: "budgets" });

Budget.belongsTo(Category, { foreignKey: "category_id", as: "category" });

export { Budget };
