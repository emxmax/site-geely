document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("mfModal");
    if (!modal) return;

    const close = () => {
        modal.classList.remove("is-open");
        document.body.style.overflow = "";
    };

    const fillSelect = (versions) => {
        const sel = document.getElementById("mfModalSelect");
        sel.innerHTML = "";

        const list = Array.isArray(versions) ? versions : [];
        if (!list.length) {
            const opt = document.createElement("option");
            opt.value = "";
            opt.textContent = "Sin versiones";
            sel.appendChild(opt);
            return;
        }

        list.forEach((v, idx) => {
            const opt = document.createElement("option");
            opt.value = String(idx);
            opt.textContent = v.name; // <-- aquí sale COMFORT 1.5 MT real
            sel.appendChild(opt);
        });
    };

    const fillSpecs = (specs) => {
        document.getElementById("mfSpecPower").textContent = specs?.maximum_power || "-";
        document.getElementById("mfSpecTransmission").textContent = specs?.transmission || "-";
        document.getElementById("mfSpecSecurity").textContent = specs?.security || "-";
        document.getElementById("mfSpecSeating").textContent = specs?.seating || "-";
        document.getElementById("mfSpecPush").textContent = specs?.push_button || "-";
    };

    const openModal = (data) => {
        document.getElementById("mfModalTitle").textContent = data.title || "";
        const imgEl = document.getElementById("mfModalImg");
        imgEl.src = data.img || "";
        imgEl.alt = data.title || "";

        document.getElementById("mfModalPrice").textContent =
            `USD ${data.usd || ""} • PEN ${data.local || ""}`;

        fillSelect(data.versions);
        fillSpecs(data.specs);

        modal.classList.add("is-open");
        document.body.style.overflow = "hidden";
    };

    document.querySelectorAll(".js-mf-open-versions").forEach(btn => {
        btn.addEventListener("click", () => {
            const raw = btn.getAttribute("data-mf");
            if (!raw) return;

            let payload = null;
            try { payload = JSON.parse(raw); } catch (e) { return; }

            openModal(payload);
        });
    });

    modal.querySelectorAll(".js-mf-close").forEach(el =>
        el.addEventListener("click", close)
    );

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") close();
    });
});
