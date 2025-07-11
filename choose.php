<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Report Incident | Transport System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
  <style>
    :root {
      --primary: #4361ee;
      --primary-dark: #3a0ca3;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --success: #4cc9f0;
      --warning: #f72585;
      --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body, html {
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(-45deg, #eef2ff, #d3f3d3, #ffe4e1, #e0f7fa);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
    }

    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .container {
      position: relative;
      height: 100vh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow: hidden;
    }

    .card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: var(--card-shadow);
      padding: 50px 40px;
      width: 100%;
      max-width: 520px;
      text-align: center;
      position: relative;
      z-index: 2;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transform: translateY(20px);
      opacity: 0;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 8px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      box-shadow: 0 4px 20px rgba(67, 97, 238, 0.3);
    }

    h1 {
      background: linear-gradient(45deg, var(--primary-dark), var(--primary));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-size: 2.2rem;
      margin-bottom: 40px;
      font-weight: 700;
      position: relative;
      display: inline-block;
    }

    h1::after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      border-radius: 2px;
    }

    .options {
      display: flex;
      flex-direction: column;
      gap: 20px;
      margin-bottom: 30px;
    }

    .option-button {
      display: flex;
      align-items: center;
      justify-content: center;
      background: white;
      color: var(--primary-dark);
      padding: 18px 24px;
      border: 2px solid var(--primary);
      border-radius: 12px;
      font-size: 18px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      position: relative;
      overflow: hidden;
    }

    .option-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      z-index: -1;
      opacity: 0;
      transition: var(--transition);
    }

    .option-button:hover {
      color: white;
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
    }

    .option-button:hover::before {
      opacity: 1;
    }

    .option-button i {
      margin-right: 12px;
      font-size: 22px;
      transition: var(--transition);
    }

    .option-button:hover i {
      transform: scale(1.1);
    }

    .divider {
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gray);
      font-weight: 500;
      font-size: 14px;
      margin: 25px 0;
      position: relative;
    }

    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.1), transparent);
      margin: 0 12px;
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.8);
      color: var(--gray);
      padding: 14px 24px;
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      margin-top: 20px;
      backdrop-filter: blur(5px);
    }

    .back-button:hover {
      background: rgba(255, 255, 255, 0.9);
      border-color: var(--primary);
      color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }

    .back-button i {
      margin-right: 8px;
      transition: var(--transition);
    }

    .back-button:hover i {
      transform: translateX(-3px);
    }

    .decoration {
      position: absolute;
      border-radius: 50%;
      background: rgba(72, 149, 239, 0.1);
      z-index: 1;
      filter: blur(40px);
      animation: float 8s ease-in-out infinite;
    }

    .decoration-1 {
      top: -50px;
      right: -50px;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(67, 97, 238, 0.15) 0%, rgba(67, 97, 238, 0) 70%);
    }

    .decoration-2 {
      bottom: -80px;
      left: -80px;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(72, 149, 239, 0.1) 0%, rgba(72, 149, 239, 0) 70%);
      animation-delay: 2s;
    }

    .decoration-3 {
      top: 50%;
      left: 50%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(244, 114, 182, 0.1) 0%, rgba(244, 114, 182, 0) 70%);
      animation-delay: 4s;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) translateX(0); }
      50% { transform: translateY(-20px) translateX(20px); }
    }

    @media (max-width: 480px) {
      .card {
        padding: 40px 20px;
      }
      
      h1 {
        font-size: 1.8rem;
        margin-bottom: 30px;
      }
      
      .option-button {
        padding: 16px 20px;
        font-size: 16px;
      }
      
      .decoration-1, .decoration-2 {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>
    <div class="decoration decoration-3"></div>
    
    <div class="card">
      <h1>Report an Incident</h1>
      
      <div class="options">
        <a href="driver.php" class="option-button">
          <i class="fas fa-id-card-alt"></i>
          <span>Report as Driver</span>
        </a>
        
        <a href="passenger.php" class="option-button">
          <i class="fas fa-user-tag"></i>
          <span>Report as Passenger</span>
        </a>
      </div>
      
      <div class="divider">or</div>
      
      <a href="index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Return to Home</span>
      </a>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Animate card on load
      gsap.to('.card', {
        duration: 0.8,
        y: 0,
        opacity: 1,
        ease: 'back.out(1)'
      });

      // Button hover effects
      const buttons = document.querySelectorAll('.option-button, .back-button');
      buttons.forEach(button => {
        button.addEventListener('mouseenter', () => {
          gsap.to(button, {
            duration: 0.3,
            y: -3,
            boxShadow: '0 10px 20px rgba(0,0,0,0.1)',
            ease: 'power2.out'
          });
        });
        button.addEventListener('mouseleave', () => {
          gsap.to(button, {
            duration: 0.3,
            y: 0,
            boxShadow: '0 4px 6px rgba(0,0,0,0.05)',
            ease: 'power2.out'
          });
        });
      });

      // Add click transition
      const links = document.querySelectorAll('a[href]');
      links.forEach(link => {
        link.addEventListener('click', function(e) {
          if(this.getAttribute('href') !== '#') {
            e.preventDefault();
            gsap.to('.card', {
              duration: 0.5,
              opacity: 0,
              y: 20,
              ease: 'power2.in',
              onComplete: () => {
                window.location.href = this.getAttribute('href');
              }
            });
          }
        });
      });
    });
  </script>
</body>
</html>