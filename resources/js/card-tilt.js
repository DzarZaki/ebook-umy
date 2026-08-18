/**
 * Card 3D Tilt Effect
 * Membuat kartu mengikuti pergerakan mouse dengan transform 3D
 */

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card-tilt');

    cards.forEach(card => {
        card.addEventListener('mousemove', handleTilt);
        card.addEventListener('mouseleave', resetTilt);
    });

    function handleTilt(e) {
        const card = e.currentTarget;
        const rect = card.getBoundingClientRect();

        // Hitung posisi mouse relatif terhadap center card (0-1)
        const x = (e.clientX - rect.left) / rect.width;
        const y = (e.clientY - rect.top) / rect.height;

        // Konversi ke sudut tilt (-10 sampai +10 derajat)
        const tiltX = (y - 0.5) * -20; // Negatif agar natural (mouse atas = tilt atas)
        const tiltY = (x - 0.5) * 20;

        // Set CSS variables untuk tilt dan shine position
        card.style.setProperty('--tilt-x', `${tiltX}deg`);
        card.style.setProperty('--tilt-y', `${tiltY}deg`);
        card.style.setProperty('--mouse-x', `${x * 100}%`);
        card.style.setProperty('--mouse-y', `${y * 100}%`);
    }

    function resetTilt(e) {
        const card = e.currentTarget;

        // Reset ke posisi default
        card.style.setProperty('--tilt-x', '0deg');
        card.style.setProperty('--tilt-y', '0deg');
        card.style.setProperty('--mouse-x', '50%');
        card.style.setProperty('--mouse-y', '50%');
    }
});
