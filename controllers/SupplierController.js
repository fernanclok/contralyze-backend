import { Supplier } from '../models/Supplier.js';

export const createSupplier = async (req, res) => {
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

        const save = await Supplier.create({
            name,
            email,
            phone,
            address,
            user_id
        });

        return res.status(201).send({
            supplier: save,
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

export const getSuppliers = async (req, res) => {
    try {
        const suppliers = await Supplier.findAll();

        return res.status(200).send({
            suppliers,
        });
    } catch (error) {
        res.status(500).json({
            error: "Internal server error",
            message: error.message,
        });
    }
}

export const getSupplier = async (req, res) => {
    try {
        const id = req.params.id;
        const supplier = await Supplier.findByPk(id);

        if (!supplier) {
            return res.status(404).json({
                error: "Not found",
                message: "Supplier not found",
            });
        }

        return res.status(200).send({
            supplier,
        });
    } catch (error) {
        res.status(500).json({
            error: "Internal server error",
            message: error.message,
        });
    }
}

export const updateSupplier = async (req, res) => {
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

        const supplier = await Supplier.findByPk(id);

        if (!supplier) {
            return res.status(404).json({
                error: "Not found",
                message: "Supplier not found",
            });
        }

        supplier.name = name;
        supplier.email = email;
        supplier.phone = phone;
        supplier.address = address;

        await supplier.save();

        return res.status(200).send({
            supplier: supplier,
        });
    } catch (error) {
        res.status(500).json({
            error: "Internal server error",
            message: error.message,
        });
    }
};