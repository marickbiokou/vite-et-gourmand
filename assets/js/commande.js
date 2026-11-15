const champPersonnes = document.getElementById("nombre_personnes");

const minimum = Number(champPersonnes.min);
const prixBase = Number(document.getElementById("prix-base").dataset.prix);

const affichagePrix = document.getElementById("prix-menu");
const affichageReduction = document.getElementById("reduction");
const affichageTotal = document.getElementById("total");

const champVille = document.getElementById("ville_livraison");

const distancesLivraison = {
  bordeaux: 0,
  merignac: 10,
  pessac: 15,
  libourne: 35,
};

const affichageLivraison = document.getElementById("frais-livraison");

function formatPrix(montant) {
  return montant.toLocaleString("fr-FR", {
    style: "currency",
    currency: "EUR",
  });
}

function calculerPrix() {
  const personnes = Number(champPersonnes.value);

  if (!Number.isInteger(personnes) || personnes < minimum) {
    affichagePrix.textContent = "—";
    affichageReduction.textContent = "—";
    affichageLivraison.textContent = "—";
    affichageTotal.textContent = "—";
    return;
  }

  const prixParPersonne = prixBase / minimum;
  const prixMenu = prixParPersonne * personnes;

  const reduction = personnes >= minimum + 5 ? prixMenu * 0.1 : 0;

  const ville = champVille.value.trim().toLowerCase();
  const distance = distancesLivraison[ville];

  if (distance === undefined) {
    affichagePrix.textContent = formatPrix(prixMenu);
    affichageReduction.textContent = formatPrix(reduction);
    affichageLivraison.textContent = "Ville non desservie";
    affichageTotal.textContent = "—";
    return;
  }

  const fraisLivraison = ville === "bordeaux" ? 0 : 5 + 0.59 * distance;
  const total = prixMenu - reduction + fraisLivraison;

  affichagePrix.textContent = formatPrix(prixMenu);
  affichageReduction.textContent = formatPrix(reduction);
  affichageLivraison.textContent = formatPrix(fraisLivraison);
  affichageTotal.textContent = formatPrix(total);
}

champPersonnes.addEventListener("input", calculerPrix);

champVille.addEventListener("input", calculerPrix);

calculerPrix();
