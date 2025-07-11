<?php
session_start(); // Start the session
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Passenger Case Reporting Platform</title>
  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
  <style>
    body {
      margin: 0;
      font-family: 'Nunito', sans-serif;
      background: linear-gradient(-45deg, #eef2ff, #d3f3d3, #ffe4e1, #e0f7fa);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      color: #333;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      opacity: 1;
      transition: opacity 0.5s ease-out;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    body.fade-out {
      opacity: 0;
    }

    .header {
      text-align: center;
      padding: 20px;
      background: rgba(255, 255, 255, 0.8);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .container {
      display: flex;
      flex: 1;
      justify-content: center;
      align-items: center;
      padding: 60px 20px;
      gap: 60px;
      flex-wrap: wrap;
    }

    .text-section {
      flex: 1;
      max-width: 600px;
    }

    .text-section h1 {
      font-size: 3.5rem;
      background: linear-gradient(45deg, #3a3aff, #6c63ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 15px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
      font-family: 'Comic Neue', cursive;
    }

    .text-section h3 {
      font-size: 1.5rem;
      color: #555;
      margin-bottom: 30px;
      position: relative;
      display: inline-block;
      font-weight: normal;
    }

    .text-section h3::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 80px;
      height: 4px;
      background: #3a3aff;
      border-radius: 2px;
    }

    .info-box {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-left: 6px solid #3a3aff;
      border-radius: 20px;
      padding: 30px;
      font-size: 1.1rem;
      line-height: 1.8;
      box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.18);
      transition: transform 0.3s ease;
    }

    .info-box:hover {
      transform: translateY(-5px);
    }

    .button-container {
      margin-top: 30px;
      display: flex;
      gap: 20px;
    }

    .interactive-btn {
      display: inline-block;
      padding: 16px 40px;
      font-size: 1.1rem;
      font-weight: bold;
      color: white;
      background: linear-gradient(135deg, #3a3aff, #6c63ff);
      border: none;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      box-shadow: 0 6px 12px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }

    .interactive-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #2a2ad8, #5a52d8);
      z-index: -1;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .interactive-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 20px rgba(58, 58, 255, 0.3);
    }

    .interactive-btn:hover::before {
      opacity: 1;
    }

    .interactive-btn:active {
      transform: translateY(2px);
    }

    .image-section {
      flex: 1;
      max-width: 500px;
      transform: translateX(20px);
      transition: transform 0.3s;
    }

    .image-section:hover {
      transform: translateX(20px) scale(1.03);
    }

    .image-section img {
      width: 100%;
      border-radius: 25px;
      object-fit: cover;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
      transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
      border: 5px solid white;
    }

    .image-section img:hover {
      transform: scale(1.02);
      box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.3);
    }

    .footer-text {
      text-align: center;
      padding: 25px;
      font-size: 1rem;
      color: white;
      background: rgba(58, 58, 255, 0.8);
      backdrop-filter: blur(5px);
      margin-top: 40px;
    }

    .footer-text strong {
      font-weight: 700;
      letter-spacing: 1px;
    }

    @media (max-width: 960px) {
      .container {
        flex-direction: column;
        text-align: center;
        gap: 30px;
      }

      .text-section, .image-section {
        max-width: 100%;
        transform: none;
      }

      .text-section h3::after {
        left: 50%;
        transform: translateX(-50%);
      }
    }

    @media (max-width: 768px) {
      .text-section h1 {
        font-size: 2.5rem;
      }
      
      .text-section h3 {
        font-size: 1.2rem;
      }
      
      .button-container {
        flex-direction: column;
        gap: 15px;
      }
      
      .interactive-btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>

<div class="header">
  <h2>Passenger Safety Platform</h2>
</div>

<div class="container">
  <div class="text-section">
    <h1>Hello!!</h1>
    <h3>Welcome to the passenger case reporting platform</h3>
    <div class="info-box">
      This platform allows passengers to report illegal or suspicious activities occurring in a vehicle in real time. The relevant authorities can be alerted and respond quickly.
    </div>
    <div class="button-container">
      <a href="choose.php" class="interactive-btn">Continue</a>
      <a href="login.php" class="interactive-btn">Login</a>
    </div>
  </div>

  <div class="image-section">
    <img src="ilkade.jpg" alt="Bus Station" />
  </div>
</div>

<div class="footer-text">
  <strong>Empowering Passengers to Make Travel Safer</strong>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Animate elements on load
    gsap.from('.text-section', { 
      duration: 0.8, 
      y: 50, 
      opacity: 0, 
      ease: 'power2.out' 
    });
    
    gsap.from('.image-section', { 
      duration: 1, 
      x: 50, 
      opacity: 0, 
      delay: 0.3, 
      ease: 'back.out(1)' 
    });
    
    // Button hover effects
    const buttons = document.querySelectorAll('.interactive-btn');
    buttons.forEach(button => {
      button.addEventListener('mouseenter', () => {
        gsap.to(button, { 
          duration: 0.3, 
          scale: 1.05, 
          boxShadow: '0 10px 20px rgba(58, 58, 255, 0.3)' 
        });
      });
      button.addEventListener('mouseleave', () => {
        gsap.to(button, { 
          duration: 0.3, 
          scale: 1, 
          boxShadow: '0 6px 12px rgba(0,0,0,0.2)' 
        });
      });
    });

    // Page transition
    buttons.forEach(button => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        document.body.classList.add("fade-out");
        setTimeout(() => {
          window.location.href = this.getAttribute("href");
        }, 500);
      });
    });
  });
</script>

</body>
</html>