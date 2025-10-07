<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма обратной связи</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main>
        <section class="contact-form-section">
            <div class="form-container">
                <h2 class="section-title">Оставьте заявку</h2>
                <div id="form-message" class="form-message"></div>
                <form id="contact-form" class="contact-form">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Ваше имя" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Ваш email" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" placeholder="Ваш телефон" required>
                    </div>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Отправить заявку
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contact-form');
            const formMessage = document.getElementById('form-message');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                formMessage.textContent = 'Отправка...';
                formMessage.className = 'form-message sending';
                
                const formData = new FormData(form);
                
                const xhr = new XMLHttpRequest();
                
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                
                                if (response.success) {
                                    formMessage.textContent = response.message;
                                    formMessage.className = 'form-message success';
                                    form.reset();
                                } else {
                                    formMessage.textContent = response.message;
                                    formMessage.className = 'form-message error';
                                }
                            } catch (e) {
                                formMessage.textContent = 'Ошибка при обработке ответа сервера';
                                formMessage.className = 'form-message error';
                            }
                        } else {
                            formMessage.textContent = 'Ошибка соединения с сервером';
                            formMessage.className = 'form-message error';
                        }
                    }
                };
                
                xhr.open('POST', 'process.php', true);
                xhr.send(formData);
            });
        });
    </script>
</body>
</html>