import { Budget } from '../models/Budget.js';

export const createBudget = async (req, res) => {
    try {
        const description = req.body?.description;
        const max_amount = req.body?.max_amount;
        const start_date = req.body?.start_date;
        const end_date = req.body?.end_date;
        const status = req.body?.status;
        const category_id = req.body?.category_id;
        const user_id = req.user_id;
    
        if (!description || !max_amount || !start_date || !end_date || !status || !category_id || !user_id) {
        const missingFields = [];
        if (!description) missingFields.push('description');
        if (!max_amount) missingFields.push('max_amount');
        if (!start_date) missingFields.push('start_date');
        if (!end_date) missingFields.push('end_date');
        if (!status) missingFields.push('status');
        if (!category_id) missingFields.push('category_id');
        if (!user_id) missingFields.push('user_id');
    
        return res.status(400).json({
            error: 'Validation error',
            message: `Missing required fields: ${missingFields.join(', ')}`,
        });
        }
    
        const budget = await Budget.create({
        description,
        max_amount,
        start_date,
        end_date,
        status,
        category_id,
        user_id,
        });
    
        return res.status(201).json({
            budget: budget,
        });
    } catch (error) {
        return res.status(500).json({
        error: 'Server error',
        message: 'An error occurred while creating the budget',
        });
    }
};