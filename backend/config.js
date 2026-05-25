const mongoose = require('mongoose');

const MONGO_URI = "mongodb+srv://bilaljarifa_db_user:<db_password>@studentmanager.wzwziwr.mongodb.net/?appName=StudentManager";

const connectDB = async () => {
    try {
        await mongoose.connect(MONGO_URI);
        console.log("✅ MongoDB connected!");
    } catch (error) {
        console.log("❌ MongoDB connection failed:", error);
        process.exit(1);
    }
};

module.exports = connectDB;