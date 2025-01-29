import { Client } from "../models/Client.js";

export const createClient = async (req, res) => {
  try {
    const name = req.body?.name;
    const email = req.body?.email;
    const phone = req.body?.phone;
    const address = req.body?.address;
    const user_id = req.user_id;

    if (!name || !email || !phone || !address) {
      const missingFields = [];
      if (!name) missingFields.push("name");
      if (!email) missingFields.push("email");
      if (!phone) missingFields.push("phone");
      if (!address) missingFields.push("address");

      return res.status(400).json({
        error: "Validation error",
        message: `Missing required fields: ${missingFields.join(", ")}`,
      });
    }

    const save = await Client.create({
      name,
      email,
      phone,
      address,
      user_id
    });

    return res.status(201).send({
      client: save,
    });
  } catch (error) {
    if (error.name === "SequelizeUniqueConstraintError") {
      return res.status(400).json({
        error: "Validation error",
        message: "Email already in use",
      });
    }

    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};

export const getClients = async (req, res) => {
  try {
    const clients = await Client.findAll();

    return res.status(200).send({
      clients,
    });
  } catch (error) {
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};

export const getClient = async (req, res) => {
  try {
    const id = req.params.id;
    const client = await Client.findByPk(id);

    if (!client) {
      return res.status(404).json({
        error: "Not found",
        message: "Client not found",
      });
    }

    return res.status(200).send({
      client,
    });
  } catch (error) {
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};

export const updateClient = async (req, res) => {
  try {
    const id = req.params.id;
    const name = req.body?.name;
    const email = req.body?.email;
    const phone = req.body?.phone;
    const address = req.body?.address;

    if (!name || !email || !phone || !address) {
      const missingFields = [];
      if (!name) missingFields.push("name");
      if (!email) missingFields.push("email");
      if (!phone) missingFields.push("phone");
      if (!address) missingFields.push("address");

      return res.status(400).json({
        error: "Validation error",
        message: `Missing required fields: ${missingFields.join(", ")}`,
      });
    }
    
    const client = await Client.findByPk(id);

    if (!client) {
      return res.status(404).json({
        error: "Not found",
        message: "Client not found",
      });
    }

    client.name = name;
    client.email = email;
    client.phone = phone;
    client.address = address;

    await client.save();

    return res.status(200).send({
      client: client,
    });
  } catch (error) {
    res.status(500).json({
      error: "Internal server error",
      message: error.message,
    });
  }
};
