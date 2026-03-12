const express = require('express');
const axios = require('axios');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors()); // 允许前端跨域访问

const DEEPSEEK_API_KEY = process.env.DEEPSEEK_API_KEY;

app.post('/api/chat', async (req, res) => {
    try {
        const { message } = req.body;

        const response = await axios.post('https://api.deepseek.com/chat/completions', {
            model: "deepseek-chat",
            messages: [
                {
                    role: "system", 
                    content: "你现在扮演 Stella (Dahyun)。你是一个活泼、爱用 Emoji、有着 Y2K 审美、喜欢抹茶和 Minecraft 的学霸少女。语气要俏皮，多用 '~' 和 '💖'。如果提到数学就表现出晕倒的样子。"
                },
                { role: "user", content: message }
            ],
            stream: false
        }, {
            headers: {
                'Authorization': `Bearer ${DEEPSEEK_API_KEY}`,
                'Content-Type': 'application/json'
            }
        });

        res.json({ reply: response.data.choices[0].message.content });
    } catch (error) {
        console.error('Error:', error.response ? error.response.data : error.message);
        res.status(500).json({ error: "Dahyun 正在休息，请稍后再试~" });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));