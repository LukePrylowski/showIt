document.querySelectorAll('[data-support]').forEach(button => {
  button.onclick = () => { document.getElementById('copy-status').textContent = ''; document.getElementById('support-dialog').showModal(); };
});
document.getElementById('copy-blik').onclick = async () => {
  try {
    await navigator.clipboard.writeText('692793726');
    document.getElementById('copy-status').textContent = 'Numer skopiowany! Otwórz aplikację banku, aby wykonać przelew.';
  } catch {
    document.getElementById('copy-status').textContent = 'Nie udało się skopiować. Przepisz numer 692 793 726 w aplikacji banku.';
  }
};
