# 1. 使用基础镜像
FROM node:18-slim

# 2. 设置工作目录
WORKDIR /app

# 3. 复制依赖文件并安装
COPY package*.json ./
RUN npm install --production

# 4. 复制其余源代码
COPY . .

# 5. 暴露端口 (Render 会自动映射)
EXPOSE 3000

# 6. 启动命令
CMD ["node", "index.js"]
