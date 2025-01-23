import { config as dotenvConfig } from 'dotenv';
import { Sequelize } from 'sequelize';

dotenvConfig();

const database = process.env.DB_NAME;
const username = process.env.DB_USER;
const password = process.env.DB_PASS;
const host = process.env.DB_HOST;

const sequelize = new Sequelize(database, username, password, {
    host: host,
    dialect: 'postgres'
});

export { sequelize };