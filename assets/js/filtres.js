const prixMin = document.getElementById("prix-min");
const prixMax = document.getElementById("prix-max");
const theme = document.getElementById("theme");
const regime = document.getElementById("regime");
const personnes = document.getElementById("personnes");

const cartes = document.querySelectorAll(".carte-menu");

function filtrerMenus() {
  const min = prixMin.value === "" ? 0 : Number(prixMin.value);
  const max = prixMax.value === "" ? Infinity : Number(prixMax.value);
  const nombre = personnes.value === "" ? 0 : Number(personnes.value);

  cartes.forEach((carte) => {
    const prixMenu = Number(carte.dataset.prix);
    const themeMenu = carte.dataset.theme;
    const regimeMenu = carte.dataset.regime;
    const minPersonnes = Number(carte.dataset.personnes);

    const correspond =
      prixMenu >= min &&
      prixMenu <= max &&
      (theme.value === "" || themeMenu === theme.value) &&
      (regime.value === "" || regimeMenu === regime.value) &&
      (nombre === 0 || minPersonnes <= nombre);

    carte.hidden = !correspond;
  });
}

[prixMin, prixMax, theme, regime, personnes].forEach((champ) => {
  champ.addEventListener("input", filtrerMenus);
});
