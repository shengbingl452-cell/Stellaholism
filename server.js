const express = require("express")
const path = require("path")
const https = require("https")

const app = express()

app.use(express.static(path.join(__dirname, "public")))
app.use(express.json({ limit: "1mb" }))

app.post("/api", (req, res) => {
    const apiKey = process.env.DEEPSEEK_API_KEY
    if (!apiKey) {
        return res.status(500).json({ error: "Missing DEEPSEEK_API_KEY" })
    }

    let messages = []
    if (Array.isArray(req.body?.messages)) {
        messages = req.body.messages
            .filter((m) => m && typeof m.role === "string" && typeof m.content === "string")
            .map((m) => ({ role: m.role, content: m.content }))
    } else if (typeof req.body?.message === "string") {
        const message = req.body.message.trim()
        if (message) {
            messages = [{ role: "user", content: message }]
        }
    }

    if (messages.length === 0) {
        return res.status(400).json({ error: "Message is required" })
    }

    const payload = JSON.stringify({
        model: "deepseek-chat",
        messages,
        stream: false
    })

    const options = {
        hostname: "api.deepseek.com",
        path: "/chat/completions",
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Content-Length": Buffer.byteLength(payload),
            "Authorization": `Bearer ${apiKey}`
        }
    }

    const upstream = https.request(options, (upstreamRes) => {
        let data = ""
        upstreamRes.on("data", (chunk) => { data += chunk })
        upstreamRes.on("end", () => {
            let parsed
            try {
                parsed = JSON.parse(data)
            } catch (err) {
                return res.status(502).json({ error: "Invalid upstream response" })
            }

            if (upstreamRes.statusCode < 200 || upstreamRes.statusCode >= 300) {
                const msg = parsed?.error?.message || "Upstream error"
                return res.status(502).json({ error: msg })
            }

            const reply = parsed?.choices?.[0]?.message?.content || ""
            return res.json({ reply })
        })
    })

    upstream.on("error", () => {
        res.status(502).json({ error: "Upstream request failed" })
    })

    upstream.write(payload)
    upstream.end()
})

app.get("/healthz",(req,res)=>{
    res.json({ status: "ok" })
})

const PORT = process.env.PORT || 3000

app.listen(PORT,()=>{
    console.log("Server running on port",PORT)
})
