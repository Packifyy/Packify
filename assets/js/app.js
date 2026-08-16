/* =========================================================
   PACKIFY — FRONTEND SYSTEM
   Customer + Courier Interaction
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    /* =====================================================
       REVEAL ANIMATION
    ===================================================== */

    const revealItems = document.querySelectorAll("[data-reveal]");

    if (revealItems.length) {

        const observer = new IntersectionObserver(
            entries => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        entry.target.classList.add("revealed");

                        observer.unobserve(entry.target);

                    }

                });

            },
            {
                threshold: 0.08
            }
        );

        revealItems.forEach(item => observer.observe(item));
    }



    /* =====================================================
       SIDEBAR ACTIVE LINK
    ===================================================== */

    const navLinks = document.querySelectorAll(".sidebar nav a");

    navLinks.forEach(link => {

        link.addEventListener("click", () => {

            navLinks.forEach(item => {
                item.classList.remove("active");
            });

            link.classList.add("active");

        });

    });



    /* =====================================================
       TOAST SYSTEM
    ===================================================== */

    window.showToast = function(message, type = "success") {

        let container = document.querySelector(".toast-container");

        if (!container) {

            container = document.createElement("div");

            container.className = "toast-container";

            document.body.appendChild(container);
        }


        const toast = document.createElement("div");

        toast.className = `packify-toast ${type}`;

        toast.innerHTML = `
            <div class="toast-icon">
                ${type === "success" ? "✓" : "!"}
            </div>

            <div class="toast-content">
                <strong>
                    ${type === "success" ? "Success" : "Attention"}
                </strong>

                <span>${message}</span>
            </div>

            <button type="button" class="toast-close">
                ×
            </button>
        `;


        container.appendChild(toast);


        requestAnimationFrame(() => {
            toast.classList.add("show");
        });


        toast.querySelector(".toast-close")
            .addEventListener("click", () => {

                removeToast(toast);

            });


        setTimeout(() => {

            removeToast(toast);

        }, 4000);
    };


    function removeToast(toast) {

        toast.classList.remove("show");

        setTimeout(() => {

            toast.remove();

        }, 300);

    }



    /* =====================================================
       MODAL SYSTEM
    ===================================================== */

    window.openModal = function(modal) {

        if (!modal) return;

        modal.classList.add("open");

        modal.setAttribute("aria-hidden", "false");

        document.body.classList.add("modal-open");

    };


    window.closeModal = function(modal) {

        if (!modal) return;

        modal.classList.remove("open");

        modal.setAttribute("aria-hidden", "true");

        document.body.classList.remove("modal-open");

    };


    document.addEventListener("click", event => {

        const closeButton =
            event.target.closest("[data-close-modal]");

        if (closeButton) {

            const modal =
                closeButton.closest(".packify-modal, .shipment-modal");

            closeModal(modal);

        }


        if (
            event.target.classList.contains("packify-modal-backdrop") ||
            event.target.classList.contains("shipment-modal-backdrop")
        ) {

            const modal =
                event.target.closest(".packify-modal, .shipment-modal");

            closeModal(modal);

        }

    });


    document.addEventListener("keydown", event => {

        if (event.key !== "Escape") return;

        const modal =
            document.querySelector(
                ".packify-modal.open, .shipment-modal.open"
            );

        if (modal) {
            closeModal(modal);
        }

    });



    /* =====================================================
       PROFILE MODAL
    ===================================================== */

    const profile = document.querySelector(".profile");

    if (profile) {

        profile.style.cursor = "pointer";

        profile.addEventListener("click", () => {

            const modal =
                document.getElementById("profileModal");

            if (modal) {

                fillProfileForm();

                openModal(modal);

            }

        });

    }


    function fillProfileForm() {

        const form =
            document.getElementById("profileForm");

        if (!form) return;

        const user =
            JSON.parse(
                localStorage.getItem("packify_user") || "{}"
            );


        const name =
            user.name ||
            document.querySelector(".profile strong")?.textContent.trim() ||
            "";


        const phone =
            user.phone || "";


        const address =
            user.address || "";


        const nameInput =
            form.querySelector('[name="name"]');

        const phoneInput =
            form.querySelector('[name="phone"]');

        const addressInput =
            form.querySelector('[name="address"]');


        if (nameInput) nameInput.value = name;

        if (phoneInput) phoneInput.value = phone;

        if (addressInput) addressInput.value = address;

    }



    /* =====================================================
       PROFILE FORM
    ===================================================== */

    const profileForm =
        document.getElementById("profileForm");


    if (profileForm) {

        profileForm.addEventListener("submit", event => {

            event.preventDefault();


            const formData =
                new FormData(profileForm);


            const user = {

                name:
                    formData.get("name")?.trim(),

                phone:
                    formData.get("phone")?.trim(),

                address:
                    formData.get("address")?.trim()

            };


            localStorage.setItem(
                "packify_user",
                JSON.stringify(user)
            );


            updateProfileUI(user);


            closeModal(
                document.getElementById("profileModal")
            );


            showToast(
                "Profile berhasil diperbarui."
            );

        });

    }



    function updateProfileUI(user) {

        if (!user.name) return;


        const profileName =
            document.querySelector(".profile strong");

        const avatar =
            document.querySelector(".profile .avatar");


        if (profileName) {
            profileName.textContent = user.name;
        }


        if (avatar) {

            avatar.textContent =
                user.name
                    .trim()
                    .charAt(0)
                    .toUpperCase();

        }

    }



    /* =====================================================
       PASSWORD FORM
    ===================================================== */

    const passwordForm =
        document.getElementById("passwordForm");


    if (passwordForm) {

        passwordForm.addEventListener("submit", event => {

            event.preventDefault();


            const oldPassword =
                passwordForm.querySelector(
                    '[name="old_password"]'
                ).value;


            const newPassword =
                passwordForm.querySelector(
                    '[name="new_password"]'
                ).value;


            const confirmPassword =
                passwordForm.querySelector(
                    '[name="confirm_password"]'
                ).value;


            if (!oldPassword) {

                showToast(
                    "Masukkan password lama.",
                    "error"
                );

                return;
            }


            if (newPassword.length < 6) {

                showToast(
                    "Password baru minimal 6 karakter.",
                    "error"
                );

                return;
            }


            if (newPassword !== confirmPassword) {

                showToast(
                    "Konfirmasi password tidak sama.",
                    "error"
                );

                return;
            }


            localStorage.setItem(
                "packify_password",
                newPassword
            );


            passwordForm.reset();


            closeModal(
                document.getElementById("passwordModal")
            );


            showToast(
                "Password berhasil diperbarui."
            );

        });

    }



    /* =====================================================
       CUSTOMER SHIPMENT STORAGE
    ===================================================== */

    const defaultShipments = [

        {
            id: "PKF-2847-01",
            description: "Dokumen kantor",
            recipient: "Customer",
            address: "Bandung, Jawa Barat",
            fragile: false,
            quantity: 1,
            status: "sedang_dikirim",
            date: "14 Aug 2026"
        },

        {
            id: "PKF-2831-09",
            description: "Pakaian",
            recipient: "Customer",
            address: "Jakarta Selatan",
            fragile: false,
            quantity: 2,
            status: "sudah_sampai",
            date: "08 Aug 2026"
        }

    ];


    function getShipments() {

        const saved =
            localStorage.getItem("packify_shipments");


        if (!saved) {

            localStorage.setItem(
                "packify_shipments",
                JSON.stringify(defaultShipments)
            );

            return defaultShipments;
        }


        try {

            return JSON.parse(saved);

        } catch {

            return defaultShipments;

        }

    }


    function saveShipments(shipments) {

        localStorage.setItem(
            "packify_shipments",
            JSON.stringify(shipments)
        );

    }



    /* =====================================================
       CUSTOMER — ADD SHIPMENT
    ===================================================== */

    const shipmentForm =
        document.getElementById("shipmentForm");


    if (shipmentForm) {

        shipmentForm.addEventListener("submit", event => {

            event.preventDefault();


            const formData =
                new FormData(shipmentForm);


            const shipments =
                getShipments();


            const shipment = {

                id:
                    generateShipmentId(),

                description:
                    formData.get("description")?.trim(),

                recipient:
                    formData.get("recipient")?.trim(),

                address:
                    formData.get("address")?.trim(),

                fragile:
                    formData.get("fragile") === "on",

                quantity:
                    Number(formData.get("quantity")) || 1,

                status:
                    "belum_dikirim",

                date:
                    formatDate(new Date())

            };


            shipments.unshift(shipment);

            saveShipments(shipments);


            shipmentForm.reset();


            closeModal(
                document.getElementById("shipmentFormModal")
            );


            renderCustomerShipments();


            showToast(
                `Shipment ${shipment.id} berhasil dibuat.`
            );

        });

    }



    function generateShipmentId() {

        const random =
            Math.floor(
                1000 +
                Math.random() * 9000
            );


        return `PKF-${random}-01`;

    }



    function formatDate(date) {

        return date.toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "short",
                year: "numeric"
            }
        );

    }



    /* =====================================================
       CUSTOMER — RENDER SHIPMENTS
    ===================================================== */

    window.renderCustomerShipments =
        function() {

            const container =
                document.getElementById(
                    "customerShipmentList"
                );


            if (!container) return;


            const shipments =
                getShipments();


            container.innerHTML = "";


            if (!shipments.length) {

                container.innerHTML = `
                    <div class="empty-state">
                        <span>○</span>

                        <strong>
                            No shipments yet
                        </strong>

                        <p>
                            Create your first shipment to get started.
                        </p>
                    </div>
                `;

                return;
            }


            shipments.forEach(shipment => {

                const row =
                    document.createElement("div");


                row.className =
                    "shipment-row";


                row.innerHTML = `

                    <div class="shipment-main">

                        <strong>
                            ${escapeHTML(shipment.id)}
                        </strong>

                        <span>
                            ${escapeHTML(shipment.description || "Package")}
                        </span>

                    </div>


                    <div class="shipment-recipient">

                        <strong>
                            ${escapeHTML(shipment.recipient)}
                        </strong>

                        <span>
                            ${escapeHTML(shipment.address)}
                        </span>

                    </div>


                    <span class="status ${shipment.status === "sudah_sampai" ? "delivered" : ""}">
                        ${getStatusLabel(shipment.status)}
                    </span>


                    <div class="shipment-actions">

                        <button
                            type="button"
                            class="table-action"
                            data-view-shipment="${shipment.id}"
                        >
                            View
                        </button>

                        ${
                            shipment.status === "belum_dikirim"
                            ? `
                                <button
                                    type="button"
                                    class="table-action"
                                    data-edit-shipment="${shipment.id}"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="table-action danger"
                                    data-cancel-shipment="${shipment.id}"
                                >
                                    Cancel
                                </button>
                            `
                            : ""
                        }

                    </div>

                `;


                container.appendChild(row);

            });

        };


    renderCustomerShipments();



    /* =====================================================
       CUSTOMER SHIPMENT ACTIONS
    ===================================================== */

    document.addEventListener("click", event => {


        const viewButton =
            event.target.closest(
                "[data-view-shipment]"
            );


        if (viewButton) {

            const id =
                viewButton.dataset.viewShipment;

            openShipmentDetail(id);

        }


        const editButton =
            event.target.closest(
                "[data-edit-shipment]"
            );


        if (editButton) {

            const id =
                editButton.dataset.editShipment;

            editShipment(id);

        }


        const cancelButton =
            event.target.closest(
                "[data-cancel-shipment]"
            );


        if (cancelButton) {

            const id =
                cancelButton.dataset.cancelShipment;

            cancelShipment(id);

        }

    });



    function editShipment(id) {

        const shipment =
            getShipments()
                .find(item => item.id === id);


        if (!shipment) return;


        const form =
            document.getElementById(
                "shipmentForm"
            );


        if (!form) return;


        form.dataset.editing = id;


        form.querySelector(
            '[name="description"]'
        ).value =
            shipment.description;


        form.querySelector(
            '[name="recipient"]'
        ).value =
            shipment.recipient;


        form.querySelector(
            '[name="address"]'
        ).value =
            shipment.address;


        form.querySelector(
            '[name="quantity"]'
        ).value =
            shipment.quantity;


        const fragile =
            form.querySelector(
                '[name="fragile"]'
            );


        if (fragile) {
            fragile.checked = shipment.fragile;
        }


        const title =
            document.querySelector(
                "#shipmentFormModal h2"
            );


        if (title) {
            title.textContent = "Edit shipment";
        }


        openModal(
            document.getElementById(
                "shipmentFormModal"
            )
        );

    }



    function cancelShipment(id) {

        const shipment =
            getShipments()
                .find(item => item.id === id);


        if (!shipment) return;


        if (
            shipment.status !==
            "belum_dikirim"
        ) {

            showToast(
                "Shipment yang sudah diproses tidak dapat dibatalkan.",
                "error"
            );

            return;
        }


        const confirmed =
            confirm(
                `Batalkan shipment ${id}?`
            );


        if (!confirmed) return;


        const shipments =
            getShipments()
                .filter(item => item.id !== id);


        saveShipments(shipments);


        renderCustomerShipments();


        showToast(
            `Shipment ${id} dibatalkan.`
        );

    }



    /* =====================================================
       SHIPMENT DETAIL
    ===================================================== */

    function openShipmentDetail(id) {

        const shipment =
            getShipments()
                .find(item => item.id === id);


        if (!shipment) return;


        const modal =
            document.getElementById(
                "shipmentDetailModal"
            );


        if (!modal) return;


        setText(
            "detailShipmentId",
            shipment.id
        );


        setText(
            "detailDescription",
            shipment.description
        );


        setText(
            "detailRecipient",
            shipment.recipient
        );


        setText(
            "detailAddress",
            shipment.address
        );


        setText(
            "detailQuantity",
            shipment.quantity
        );


        const badge =
            document.getElementById(
                "detailStatus"
            );


        if (badge) {

            badge.textContent =
                getStatusLabel(
                    shipment.status
                );

            badge.className =
                "status " +
                (
                    shipment.status ===
                    "sudah_sampai"
                        ? "delivered"
                        : ""
                );

        }


        openModal(modal);

    }



    function setText(id, value) {

        const element =
            document.getElementById(id);

        if (element) {
            element.textContent =
                value ?? "-";
        }

    }



    function getStatusLabel(status) {

        const labels = {

            belum_dikirim:
                "Not shipped",

            sedang_dikirim:
                "In transit",

            sudah_sampai:
                "Delivered"

        };


        return labels[status] || status;

    }



    /* =====================================================
       EDIT FORM SUBMIT
    ===================================================== */

    if (shipmentForm) {

        shipmentForm.addEventListener(
            "submit",
            event => {

                const editingId =
                    shipmentForm.dataset.editing;


                if (!editingId) return;


                event.preventDefault();


                const formData =
                    new FormData(shipmentForm);


                const shipments =
                    getShipments();


                const index =
                    shipments.findIndex(
                        item =>
                            item.id === editingId
                    );


                if (index === -1) return;


                shipments[index].description =
                    formData.get("description")
                        ?.trim();


                shipments[index].recipient =
                    formData.get("recipient")
                        ?.trim();


                shipments[index].address =
                    formData.get("address")
                        ?.trim();


                shipments[index].quantity =
                    Number(
                        formData.get("quantity")
                    ) || 1;


                shipments[index].fragile =
                    formData.get("fragile") === "on";


                saveShipments(shipments);


                delete shipmentForm.dataset.editing;


                shipmentForm.reset();


                const title =
                    document.querySelector(
                        "#shipmentFormModal h2"
                    );


                if (title) {
                    title.textContent =
                        "Create shipment";
                }


                closeModal(
                    document.getElementById(
                        "shipmentFormModal"
                    )
                );


                renderCustomerShipments();


                showToast(
                    `Shipment ${editingId} berhasil diperbarui.`
                );

            }
        );

    }



    /* =====================================================
       COURIER DATA
    ===================================================== */

    const courierShipments = [

        {
            id: "PKF-2850-03",
            sender: "Customer",
            recipient: "Customer",
            from: "Jakarta",
            to: "Bekasi",
            status: "belum_dikirim"
        },

        {
            id: "PKF-2847-01",
            sender: "Customer",
            recipient: "Customer",
            from: "Jakarta",
            to: "Bandung",
            status: "belum_dikirim"
        },

        {
            id: "PKF-2846-22",
            sender: "Customer",
            recipient: "Customer",
            from: "Jakarta",
            to: "Cimahi",
            status: "sedang_dikirim"
        },

        {
            id: "PKF-2841-18",
            sender: "Customer",
            recipient: "Customer",
            from: "Depok",
            to: "Bandung",
            status: "sudah_sampai"
        }

    ];


    function getCourierShipments() {

        const saved =
            localStorage.getItem(
                "packify_courier_shipments"
            );


        if (!saved) {

            localStorage.setItem(
                "packify_courier_shipments",
                JSON.stringify(courierShipments)
            );

            return courierShipments;

        }


        return JSON.parse(saved);

    }


    function saveCourierShipments(data) {

        localStorage.setItem(
            "packify_courier_shipments",
            JSON.stringify(data)
        );

    }



    /* =====================================================
       COURIER — TAKE SHIPMENT
    ===================================================== */

    document.addEventListener(
        "click",
        event => {

            const takeButton =
                event.target.closest(
                    "[data-take-shipment]"
                );


            if (!takeButton) return;


            const id =
                takeButton.dataset.takeShipment;


            const shipments =
                getCourierShipments();


            const shipment =
                shipments.find(
                    item => item.id === id
                );


            if (!shipment) return;


            if (
                shipment.status !==
                "belum_dikirim"
            ) {

                showToast(
                    "Paket ini sudah diambil courier.",
                    "error"
                );

                return;

            }


            shipment.status =
                "sedang_dikirim";


            saveCourierShipments(
                shipments
            );


            renderCourierShipments();


            showToast(
                `Paket ${id} berhasil diambil.`
            );

        }
    );



    /* =====================================================
       COURIER — CANCEL TAKE
    ===================================================== */

    document.addEventListener(
        "click",
        event => {

            const button =
                event.target.closest(
                    "[data-cancel-take]"
                );


            if (!button) return;


            const id =
                button.dataset.cancelTake;


            const shipments =
                getCourierShipments();


            const shipment =
                shipments.find(
                    item => item.id === id
                );


            if (!shipment) return;


            shipment.status =
                "belum_dikirim";


            saveCourierShipments(
                shipments
            );


            renderCourierShipments();


            showToast(
                `Pengambilan ${id} dibatalkan.`
            );

        }
    );



    /* =====================================================
       COURIER — MARK AS DELIVERED
    ===================================================== */

    document.addEventListener(
        "click",
        event => {

            const button =
                event.target.closest(
                    "[data-complete-shipment]"
                );


            if (!button) return;


            const id =
                button.dataset.completeShipment;


            const shipments =
                getCourierShipments();


            const shipment =
                shipments.find(
                    item => item.id === id
                );


            if (!shipment) return;


            if (
                shipment.status !==
                "sedang_dikirim"
            ) {

                showToast(
                    "Paket belum dalam status pengiriman.",
                    "error"
                );

                return;

            }


            shipment.status =
                "sudah_sampai";


            saveCourierShipments(
                shipments
            );


            renderCourierShipments();


            showToast(
                `Shipment ${id} ditandai sudah sampai.`
            );

        }
    );



    /* =====================================================
       COURIER — RENDER
    ===================================================== */

    window.renderCourierShipments =
        function() {

            const container =
                document.getElementById(
                    "courierShipmentList"
                );


            if (!container) return;


            const shipments =
                getCourierShipments();


            container.innerHTML = "";


            shipments.forEach(shipment => {

                const row =
                    document.createElement("div");


                row.className =
                    "shipment-row";


                row.innerHTML = `

                    <div class="shipment-main">

                        <strong>
                            ${escapeHTML(shipment.id)}
                        </strong>

                        <span>
                            ${escapeHTML(shipment.sender)}
                        </span>

                    </div>


                    <div class="shipment-recipient">

                        <strong>
                            ${escapeHTML(shipment.from)}
                            →
                            ${escapeHTML(shipment.to)}
                        </strong>

                        <span>
                            ${escapeHTML(shipment.recipient)}
                        </span>

                    </div>


                    <span class="status ${
                        shipment.status ===
                        "sudah_sampai"
                            ? "delivered"
                            : ""
                    }">

                        ${getStatusLabel(shipment.status)}

                    </span>


                    <div class="shipment-actions">

                        <button
                            type="button"
                            class="table-action"
                            data-view-courier="${shipment.id}"
                        >
                            View
                        </button>

                        ${
                            shipment.status ===
                            "belum_dikirim"
                            ? `
                                <button
                                    type="button"
                                    class="table-action"
                                    data-take-shipment="${shipment.id}"
                                >
                                    Take
                                </button>
                            `
                            : ""
                        }

                        ${
                            shipment.status ===
                            "sedang_dikirim"
                            ? `
                                <button
                                    type="button"
                                    class="table-action"
                                    data-cancel-take="${shipment.id}"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="table-action primary"
                                    data-complete-shipment="${shipment.id}"
                                >
                                    Delivered
                                </button>
                            `
                            : ""
                        }

                    </div>

                `;


                container.appendChild(row);

            });

        };


    renderCourierShipments();



    /* =====================================================
       COURIER TABS
    ===================================================== */

    const courierTabs =
        document.querySelectorAll(
            "[data-courier-tab]"
        );


    courierTabs.forEach(tab => {

        tab.addEventListener(
            "click",
            () => {

                courierTabs.forEach(item => {

                    item.classList.remove(
                        "active"
                    );

                });


                tab.classList.add(
                    "active"
                );


                const target =
                    tab.dataset.courierTab;


                const rows =
                    document.querySelectorAll(
                        "#courierShipmentList .shipment-row"
                    );


                rows.forEach(row => {

                    if (target === "all") {

                        row.style.display = "";

                        return;

                    }


                    const status =
                        row.querySelector(
                            ".status"
                        )?.textContent
                            .trim()
                            .toLowerCase();


                    if (
                        target === "mine" &&
                        status === "in transit"
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display =
                            target === "mine"
                                ? "none"
                                : "";

                    }
                });
            }
        );
    });

    function escapeHTML(value) {

        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");

    }

});

/* =========================================================
   PACKIFY — COURIER INTERACTION
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("shipmentModal");

    if (!modal) return;

    const modalCard = modal.querySelector(".shipment-modal-card");
    const closeButton = document.getElementById("modalClose");
    const backdrop = modal.querySelector(".shipment-modal-backdrop");

    const modalId = document.getElementById("modalShipmentId");
    const modalStatus = document.getElementById("modalStatus");
    const modalStatusBadge = document.getElementById("modalStatusBadge");

    const modalFrom = document.getElementById("modalFrom");
    const modalTo = document.getElementById("modalTo");
    const modalSchedule = document.getElementById("modalSchedule");
    const modalArrival = document.getElementById("modalArrival");

    const timelinePickup =
        document.getElementById("timelinePickup");

    const timelineTransit =
        document.getElementById("timelineTransit");

    const timelineDelivered =
        document.getElementById("timelineDelivered");

    const actionButton =
        document.getElementById("shipmentAction");


    /* -----------------------------------------------------
       DEMO SHIPMENT DATA

       Nanti tinggal diganti response API backend.
    ----------------------------------------------------- */

    const shipments = {

        "PKF-2841-18": {
            id: "PKF-2841-18",
            status: "Delivered",
            statusText: "Package successfully delivered",
            from: "Depok",
            to: "Bandung",
            schedule: "13:30",
            arrival: "Delivered",
            step: 3
        },

        "PKF-2846-22": {
            id: "PKF-2846-22",
            status: "In transit",
            statusText: "Package is currently on route",
            from: "Jakarta",
            to: "Cimahi",
            schedule: "11:00",
            arrival: "Today",
            step: 2
        },

        "PKF-2847-01": {
            id: "PKF-2847-01",
            status: "Pickup",
            statusText: "Pickup scheduled",
            from: "Jakarta",
            to: "Bandung",
            schedule: "09:30",
            arrival: "Today",
            step: 1
        },

        "PKF-2850-03": {
            id: "PKF-2850-03",
            status: "Pickup",
            statusText: "Pickup scheduled",
            from: "Jakarta",
            to: "Bekasi",
            schedule: "15:00",
            arrival: "Today",
            step: 1
        }

    };


    /* -----------------------------------------------------
       OPEN MODAL
    ----------------------------------------------------- */

    function openShipment(id) {

        const shipment = shipments[id];

        if (!shipment) return;

        modalId.textContent =
            shipment.id;

        modalStatus.textContent =
            shipment.statusText;

        modalStatusBadge.textContent =
            shipment.status;

        modalFrom.textContent =
            shipment.from;

        modalTo.textContent =
            shipment.to;

        modalSchedule.textContent =
            shipment.schedule;

        modalArrival.textContent =
            shipment.arrival;


        updateTimeline(
            shipment.step
        );

        updateAction(
            shipment.status
        );


        modal.classList.add("is-open");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-open"
        );

        setTimeout(() => {
            closeButton?.focus();
        }, 100);

    }


    /* -----------------------------------------------------
       CLOSE MODAL
    ----------------------------------------------------- */

    function closeShipmentModal() {

        modal.classList.remove(
            "is-open"
        );

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-open"
        );

    }


    /* -----------------------------------------------------
       TIMELINE
    ----------------------------------------------------- */

    function updateTimeline(step) {

        const items = [
            timelinePickup,
            timelineTransit,
            timelineDelivered
        ];

        items.forEach((item, index) => {

            if (!item) return;

            const currentStep =
                index + 1;

            item.classList.remove(
                "completed",
                "current"
            );

            if (currentStep < step) {

                item.classList.add(
                    "completed"
                );

            }

            if (currentStep === step) {

                item.classList.add(
                    "current"
                );

            }

        });

    }


    /* -----------------------------------------------------
       ACTION BUTTON
    ----------------------------------------------------- */

    function updateAction(status) {

        if (!actionButton) return;

        if (status === "Pickup") {

            actionButton.innerHTML =
                'Start pickup <span>→</span>';

            actionButton.dataset.action =
                "pickup";

            return;
        }


        if (status === "In transit") {

            actionButton.innerHTML =
                'Mark as delivered <span>✓</span>';

            actionButton.dataset.action =
                "delivered";

            return;
        }


        actionButton.innerHTML =
            'Shipment completed <span>✓</span>';

        actionButton.dataset.action =
            "completed";

    }


    /* -----------------------------------------------------
       ROUTE ITEMS
    ----------------------------------------------------- */

    document
        .querySelectorAll(".courier-route-item")
        .forEach(item => {

            item.addEventListener(
                "click",
                event => {

                    if (
                        event.target.closest("button")
                    ) {
                        return;
                    }

                    const id =
                        item.querySelector(
                            ".route-title strong"
                        )?.textContent.trim();

                    if (id) {
                        openShipment(id);
                    }

                }
            );

            item.setAttribute(
                "tabindex",
                "0"
            );

            item.setAttribute(
                "role",
                "button"
            );


            item.addEventListener(
                "keydown",
                event => {

                    if (
                        event.key === "Enter" ||
                        event.key === " "
                    ) {

                        event.preventDefault();

                        const id =
                            item.querySelector(
                                ".route-title strong"
                            )?.textContent.trim();

                        if (id) {
                            openShipment(id);
                        }

                    }

                }
            );

        });


    /* -----------------------------------------------------
       NEXT STOP BUTTON
    ----------------------------------------------------- */

    document
        .querySelectorAll(".route-button")
        .forEach(button => {

            button.addEventListener(
                "click",
                event => {

                    event.preventDefault();

                    const card =
                        button.closest(
                            ".next-stop-card, .delivery-focus"
                        );

                    const id =
                        card?.querySelector(
                            "h2, h3"
                        )?.textContent.trim();

                    if (id && shipments[id]) {

                        openShipment(id);

                    } else {

                        showCourierToast(
                            "Delivery detail opened."
                        );

                    }

                }
            );

        });


    /* -----------------------------------------------------
       MODAL ACTION
    ----------------------------------------------------- */

    actionButton?.addEventListener(
        "click",
        () => {

            const action =
                actionButton.dataset.action;

            const id =
                modalId.textContent.trim();

            if (action === "pickup") {

                actionButton.innerHTML =
                    'Pickup started <span>✓</span>';

                actionButton.dataset.action =
                    "delivered";

                modalStatus.textContent =
                    "Package picked up";

                modalStatusBadge.textContent =
                    "In transit";

                updateTimeline(2);

                showCourierToast(
                    `${id} is now in transit.`
                );

                return;
            }


            if (action === "delivered") {

                actionButton.innerHTML =
                    'Shipment completed <span>✓</span>';

                actionButton.dataset.action =
                    "completed";

                modalStatus.textContent =
                    "Package delivered";

                modalStatusBadge.textContent =
                    "Delivered";

                updateTimeline(3);

                showCourierToast(
                    `${id} marked as delivered.`
                );

                return;
            }


            showCourierToast(
                "Shipment already completed."
            );

        }
    );


    /* -----------------------------------------------------
       CLOSE EVENTS
    ----------------------------------------------------- */

    closeButton?.addEventListener(
        "click",
        closeShipmentModal
    );

    backdrop?.addEventListener(
        "click",
        closeShipmentModal
    );


    document.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Escape" &&
                modal.classList.contains("is-open")
            ) {

                closeShipmentModal();

            }

        }
    );


    /* -----------------------------------------------------
       TOAST
    ----------------------------------------------------- */

    function showCourierToast(message) {

        const oldToast =
            document.querySelector(
                ".courier-toast"
            );

        oldToast?.remove();


        const toast =
            document.createElement("div");

        toast.className =
            "courier-toast";

        toast.textContent =
            message;

        document.body.appendChild(
            toast
        );


        setTimeout(() => {

            toast.classList.add(
                "hide"
            );

            setTimeout(() => {
                toast.remove();
            }, 300);

        }, 2500);

    }

});