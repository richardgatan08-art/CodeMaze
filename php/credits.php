<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Credits</title>
<link rel="icon" href="../image/logo.png">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Segoe UI", sans-serif;
}

body {
    background: linear-gradient(135deg, #1e1e2f, #2b2b45);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.credits {
    text-align: center;
    color: #fff;
    width: 90%;
    max-width: 1100px;
}

.credits h1 {
    margin-bottom: 30px;
    font-size: 2.5rem;
    letter-spacing: 2px;
}

.credit-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.credit-box {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px 20px;
    border-radius: 15px;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.credit-box:hover {
    transform: translateY(-10px) scale(1.03);
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}

.credit-box img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 3px solid #ffd369;
}

.credit-box h2 {
    margin-bottom: 8px;
    color: #ffd369;
    font-size: 1.4rem;
}

.credit-box p {
    font-size: 1.1rem;
    opacity: 0.9;
}
.back-button {
    position: absolute;
    top: 15px;
    left: 10px;
    padding: 8px 14px;
    background: rgba(0, 247, 255, 0.1);
    color: rgb(255, 249, 249);
    border: 1px solid #00f7ff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}

.back-button:hover {
    background: #00f7ff;
    color: #11152b;
    box-shadow: 0 0 10px #00f7ff, 0 0 20px #00f7ff;
}
</style>
</head>

<body>
 <a href="buttons.php" class="back-button">⬅ Back</a> 
<section class="credits">
    <h1>Project Credits</h1>

    <div class="credit-container">

        <div class="credit-box">
            <img src="../image/g.jpg" alt="Richard Gatan">
            <h2>Project:Manager / Back-End</h2>
            <p>Richard Gatan</p>
        </div>

        <div class="credit-box">
            <img src="../image/julie.jpg" alt="Julie Grace Gepanaga">
            <h2>Back-End Developer</h2>
            <p>Julie Grace Gepanaga</p>
        </div>

        <div class="credit-box">
            <img src="../image/ferrer.jpg" alt="Nicole Jade Ferrer">
            <h2>Front - End Developer</h2>
            <p>Nicole Jade Ferrer</p>
        </div>

        <div class="credit-box">
            <img src="../image/cristel.jpg" alt="Cristy Marie Pintor">
            <h2>Front - End Developer</h2>
            <p>Cristy Marie Pintor</p>
        </div>

    </div>
</section>

</body>
</html>
