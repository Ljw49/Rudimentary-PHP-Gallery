## 📸 简易 PHP 相册 / Rudimentary PHP Gallery

### 🇨🇳 简介（Chinese Introduction）

这是一款 **超级轻量级的 PHP 在线相册**，无需数据库、无需依赖库，只需要能运行 PHP 的环境即可。
非常适合部署在 **低功耗边缘设备**（如 ARM开发版、树莓派、旧 PC 等）上，用于家庭局域网图片浏览。

项目由 ChatGPT 协助优化完成，针对 **桌面端与移动端** 都做了适配与交互优化。

---

### 🌟 特点 / Features

* 🪶 **超轻量**：整个项目只有一个 PHP 文件 + 一些图片，不依赖数据库或框架。
* ⚙️ **低资源占用**：128MB 内存的小板子都能流畅运行。
* 💻 **跨设备自适应**：同时适配桌面端与移动端（支持 iPad、手机、PC）。
* 🖼️ **格式广泛支持**：支持 JPG、PNG、GIF、WEBP、AVIF、HEIC、HEIF 等现代格式（视乎浏览器的支持）。
* 🌐 **即开即用**：只需 PHP 内置 Web 服务器，即可快速在局域网访问。
* 🪄 **完全离线使用**：无需外网依赖，非常适合家庭 NAS、本地相册等场景。

---

### 🧩 核心功能 / Core Features

* 自动扫描部署目录下的所有图片文件；
* 按文件名排序展示缩略图；
* 支持图片查看、**上一张 / 下一张** 切换；
* 支持 **放大 / 缩小 / 拖动**；
* 支持 **全屏查看** 模式；
* 自适应移动端手势操作。

---

### 🖥️ 操作逻辑 / User Interaction

#### 💻 桌面端 Desktop:

* ⬆️ **上箭头**：放大
* ⬇️ **下箭头**：缩小
* ⬅️➡️ **左右箭头**：切换上一张 / 下一张
* 🖱️ **鼠标滚轮**：放大 / 缩小
* 🖱️ **鼠标左键拖动**：移动图片位置
* 🖵 **全屏按钮**：切换全屏，ESC 或再次点击退出

#### 📱 移动端 Mobile:

* 👆 **单指左右滑动**：切换上一张 / 下一张
* ✌️ **双指捏合**：放大 / 缩小
* 🖼️ **放大后单指拖动**：移动图片位置
* 📲 **全屏下下滑**：退出全屏

---

### ⚙️ 部署步骤 / Deployment Steps

#### 1️⃣ 安装 PHP（Debian / Ubuntu）

```bash
sudo apt update
sudo apt install -y php php-cli php-gd php-exif php-mbstring
```

#### 2️⃣ 创建目录并复制文件

```bash
sudo mkdir -p /srv/photo_gallery/html
cd /srv/photo_gallery/html
cp /path/to/gallery.php /srv/photo_gallery/html/
# mkdir 相册 # 拷贝照片操作
# cp ~/Pictures/*.jpg 相册/
```

目录结构示例：

```
/srv/photo_gallery/html
├── gallery.php
└── 相册
    ├── DSC0001.jpg
    ├── DSC0002.avif
    └── DSC0003.heic
```

#### 3️⃣ 启动内置 PHP 服务

```bash
cd /srv/photo_gallery/html
php -S 0.0.0.0:8080
```
需要在gallery.php的目录下启动PHP服务，访问的范围也在该文件夹。

#### 4️⃣ 浏览器访问

* 本机访问：`http://localhost:8080/gallery.php`
* 局域网访问：`http://<主机IP>:8080/gallery.php`

---

### ⚠️ 注意事项 / Important Notes

* 🔒 **无登录、无加密功能**：仅适用于家庭或可信局域网使用。
* 🧱 **无数据库依赖**：所有图片直接从文件夹中读取。
* 🈚 **暂不支持多语言界面**（当前为中文界面，未来可扩展）。
* 🚫 **不建议暴露至公网**：本项目没有认证机制，不适合直接公开访问。
* 📂 **图片数量较多时** 建议放在子目录中分层管理。
* 🧠 **浏览器兼容性**：建议使用现代浏览器（Chrome / Edge / Safari / Firefox）。

---

### 🧠 设计理念 / Philosophy

> 让几乎任何低功耗设备都能作为服务主机，桌面端和移动端都能快速浏览照片。
> 只需 PHP，一个文件，一台小主机，即可拥有极简而流畅的在线相册体验。
> 最简单的观看体验 -> 全屏幕，可放大看细节。

---

### 🪧 关于 / About

* 项目名称：**Simple PHP Gallery for Edge Devices**
* 作者：Ljw49
* 协作：由 GPT-5 协助开发与优化
* 许可证：MIT License

---
### 界面截图/UI Screenshot
<img width="506" height="235" alt="image" src="https://github.com/user-attachments/assets/6b85c0a3-0344-496d-92f2-e4e2bb9a4760" />
---


### 🌍 English Section

#### 📘 Introduction

This is a **super lightweight PHP-based photo gallery**, requiring **no database** and **no external libraries**.
It runs perfectly on **low-power edge devices** (e.g., cheap development board, Raspberry Pi, or old x86 PCs) for **local network use**.

Fully adapted for both **desktop** and **mobile** browsers.
Developed and refined with the assistance of GPT-5.

---

#### 🌟 Features

* 🪶 **Ultra lightweight** — single PHP file, no frameworks.
* ⚙️ **Minimal resource usage**, works smoothly on devices with 128 MB RAM.
* 💻 **Responsive layout** — supports PC, tablet, and mobile.
* 🖼️ **Supports modern formats**: JPG, PNG, GIF, WEBP, AVIF, HEIC, HEIF.
* 🌐 **Instant local deployment** using PHP’s built-in server.
* 🪄 **Completely offline** — ideal for home NAS or LAN galleries.

---

#### 🧩 Core Functions

* Automatically scans image files in the deployment folder.
* Displays thumbnail grid view.
* Supports **previous/next** navigation.
* Supports **zoom in/out**, **drag**, and **fullscreen view**.
* Touch gestures for mobile devices.

---

#### 🖥️ Controls

**Desktop:**

* ⬆️ Arrow Up → Zoom in
* ⬇️ Arrow Down → Zoom out
* ⬅️➡️ Left/Right Arrows → Previous/Next image
* 🖱️ Mouse wheel → Zoom in/out
* 🖱️ Left click & drag → Move image
* 🖵 Fullscreen button → Toggle fullscreen (ESC to exit)

**Mobile:**

* 👆 Swipe left/right → Change image
* ✌️ Pinch → Zoom in/out
* 🖼️ Drag after zoom → Move image
* 📲 Swipe down in fullscreen → Exit fullscreen

---

#### ⚙️ Deployment

1. **Install PHP**

   ```bash
   sudo apt install -y php php-cli php-gd php-exif php-mbstring
   ```

2. **Create folder and copy files**

   ```bash
   sudo mkdir -p /srv/photo_gallery/html
   cp gallery.php /srv/photo_gallery/html/
   mkdir /srv/photo_gallery/html/photos
   cp ~/Pictures/*.jpg /srv/photo_gallery/html/photos/
   ```

3. **Start server**

   ```bash
   cd /srv/photo_gallery/html
   php -S 0.0.0.0:8080
   ```
   You need to start the PHP service in the gallery.php directory, and access is restricted to that folder.

4. **Access**

   * Local: `http://localhost:8080/gallery.php`
   * LAN: `http://<your_ip>:8080/gallery.php`

---

#### ⚠️ Notes

* No authentication or encryption → **LAN use only**.
* No database required.
* Single-language (Chinese interface only, can be extended).
* Avoid exposing to the public internet.
* For many images, use subfolders for organization.
* Modern browser recommended.

---

#### 🧠 Philosophy

> Enable nearly any low-power device to host services, allowing quick photo browsing on both desktop and mobile.
> With just PHP, a single file, and a small server, enjoy a minimalist and seamless online photo album experience.
> Simplest viewing experience -> Full-screen mode with zoom capability for detailed inspection.

---

#### 🪧 About

* Project: **Simple PHP Gallery for Edge Devices**
* Author: Ljw49
* Assisted by GPT-5
* License: MIT License

---
