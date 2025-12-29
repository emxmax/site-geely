(() => {
  const equalizeCardHeights = () => {
    // Solo aplicar igualación de alturas en desktop (>= 1024px)
    if (window.innerWidth < 1024) return;

    const cardContents = document.querySelectorAll(
      `#new-featured .new-featured__content`
    );

    if (cardContents.length === 0) return;

    // Resetear altura para obtener altura natural
    resetCardHeights();

    // Encontrar la altura máxima
    const maxHeight = getMaxCardHeight(cardContents);

    // Aplicar la altura máxima a todos los elementos
    applyHeightToCards(cardContents, maxHeight);
  };

  const resetCardHeights = () => {
    const cardContents = document.querySelectorAll(
      `#new-featured .new-featured__content`
    );

    cardContents.forEach((card) => {
      card.style.height = "auto";
    });
  };

  const getMaxCardHeight = (cards) => {
    let maxHeight = 0;

    cards.forEach((card) => {
      const cardHeight = card.offsetHeight;
      if (cardHeight > maxHeight) {
        maxHeight = cardHeight;
      }
    });

    return maxHeight;
  };

  const applyHeightToCards = (cards, height) => {
    cards.forEach((card) => {
      card.style.height = `${height}px`;
    });
  };

  const initDomReady = () => {
    equalizeCardHeights();

    window.addEventListener("resize", equalizeCardHeights);
  };

  document.addEventListener("DOMContentLoaded", initDomReady);
})();
