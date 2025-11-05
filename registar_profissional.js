const checkboxes = document.querySelectorAll('input[name="categorias[]"]');
checkboxes.forEach(cb => {
    cb.addEventListener('change', () => {
        const checked = document.querySelectorAll('input[name="categorias[]"]:checked');
        if (checked.length > 3) cb.checked = false;
    });
});

// Pesquisa de categorias
const search = document.getElementById('searchCat');
search.addEventListener('input', () => {
    const term = search.value.toLowerCase();
    document.querySelectorAll('.categoria-item').forEach(label => {
        label.style.display = label.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
    });
});