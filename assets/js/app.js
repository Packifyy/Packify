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

    document.addEventListener("click", event => {

    const editButton =
        event.target.closest("[data-edit-shipment]");

    if (!editButton) return;

    const form =
        document.getElementById("shipmentForm");

    if (!form) return;

    const modal =
        document.getElementById("shipmentFormModal");

    const actionInput =
        document.getElementById("shipmentAction");

    const shipmentId =
        document.getElementById("shipmentId");

    const submitButton =
        document.getElementById("shipmentSubmitButton");


    shipmentId.value =
        editButton.dataset.editShipment;

    actionInput.value =
        "update_shipment";


    const recipient =
        form.querySelector('[name="recipient"]');

    const address =
        form.querySelector('[name="address"]');


    if (recipient) {
        recipient.value =
            editButton.dataset.recipient || "";
    }

    if (address) {
        address.value =
            editButton.dataset.address || "";
    }


    const title =
        modal?.querySelector("h2");

    if (title) {
        title.textContent =
            "Edit shipment";
    }


    if (submitButton) {
        submitButton.textContent =
            "Update shipment";
    }


    openModal(modal);

});
});
/* =====================================================
   CUSTOMER — DATABASE SHIPMENT
===================================================== */

const shipmentForm =
    document.getElementById("shipmentForm");


/*
|--------------------------------------------------------------------------
| CREATE SHIPMENT
|--------------------------------------------------------------------------
| Form dikirim langsung ke PHP.
| Jangan pakai preventDefault().
| PHP yang akan INSERT ke MySQL.
*/

if (shipmentForm) {

    shipmentForm.addEventListener("submit", event => {

        const submitButton =
            shipmentForm.querySelector(
                'button[type="submit"]'
            );

        if (submitButton) {

            submitButton.disabled = true;

            submitButton.textContent =
                "Saving...";

        }

    });

}


/*
|--------------------------------------------------------------------------
| VIEW SHIPMENT
|--------------------------------------------------------------------------
*/

document.addEventListener("click", event => {

    const viewButton =
        event.target.closest(
            "[data-view-shipment]"
        );

    if (!viewButton) return;

    const id =
        viewButton.dataset.viewShipment;

    openShipmentDetail(id);

});


/*
|--------------------------------------------------------------------------
| CANCEL SHIPMENT
|--------------------------------------------------------------------------
|
| Cancel dikirim ke PHP.
|
*/

document.addEventListener("click", event => {

    const cancelButton =
        event.target.closest(
            "[data-cancel-shipment]"
        );

    if (!cancelButton) return;

    const id =
        cancelButton.dataset.cancelShipment;

    if (!id) return;

    const confirmed =
        confirm(
            `Batalkan shipment ${id}?`
        );

    if (!confirmed) return;


    const form =
        document.createElement("form");

    form.method = "POST";
    form.action = "customer-dashboard.php";
    form.style.display = "none";


    const action =
        document.createElement("input");

    action.type = "hidden";
    action.name = "action";
    action.value = "cancel_shipment";


    const tracking =
        document.createElement("input");

    tracking.type = "hidden";
    tracking.name = "tracking_number";
    tracking.value = id;


    form.appendChild(action);
    form.appendChild(tracking);

    document.body.appendChild(form);

    form.submit();

});


/*
|--------------------------------------------------------------------------
| EDIT SHIPMENT
|--------------------------------------------------------------------------
|
| Edit juga dikirim ke PHP.
|
*/

document.addEventListener("click", event => {

    const editButton =
        event.target.closest(
            "[data-edit-shipment]"
        );

    if (!editButton) return;

    const id =
        editButton.dataset.editShipment;

    const form =
        document.getElementById(
            "shipmentForm"
        );

    if (!form) return;


    form.dataset.editing = id;


    const description =
        editButton.dataset.description || "";

    const recipient =
        editButton.dataset.recipient || "";

    const address =
        editButton.dataset.address || "";

    const quantity =
        editButton.dataset.quantity || "1";


    const descriptionInput =
        form.querySelector(
            '[name="description"]'
        );

    const recipientInput =
        form.querySelector(
            '[name="recipient"]'
        );

    const addressInput =
        form.querySelector(
            '[name="address"]'
        );

    const quantityInput =
        form.querySelector(
            '[name="quantity"]'
        );


    if (descriptionInput)
        descriptionInput.value =
            description;

    if (recipientInput)
        recipientInput.value =
            recipient;

    if (addressInput)
        addressInput.value =
            address;

    if (quantityInput)
        quantityInput.value =
            quantity;


    const title =
        document.querySelector(
            "#shipmentFormModal h2"
        );

    if (title) {

        title.textContent =
            "Edit shipment";

    }


    openModal(
        document.getElementById(
            "shipmentFormModal"
        )
    );

});


/*
|--------------------------------------------------------------------------
| SHIPMENT DETAIL
|--------------------------------------------------------------------------
*/

function openShipmentDetail(id) {

    const modal =
        document.getElementById(
            "shipmentDetailModal"
        );

    if (!modal) return;


    const idElement =
        document.getElementById(
            "detailShipmentId"
        );


    if (idElement) {

        idElement.textContent =
            id;

    }


    /*
    | Kalau detail sudah dirender PHP,
    | kita ambil data dari row yang diklik.
    */

    const button =
        document.querySelector(
            `[data-view-shipment="${CSS.escape(id)}"]`
        );


    if (button) {

        setText(
            "detailDescription",
            button.dataset.description
        );

        setText(
            "detailRecipient",
            button.dataset.recipient
        );

        setText(
            "detailAddress",
            button.dataset.address
        );

        setText(
            "detailQuantity",
            button.dataset.quantity
        );


        const status =
            button.dataset.status || "";


        const badge =
            document.getElementById(
                "detailStatus"
            );


        if (badge) {

            badge.textContent =
                getStatusLabel(status);

            badge.className =
                "status " +
                (
                    status === "sudah_sampai"
                        ? "delivered"
                        : ""
                );

        }

    }


    openModal(modal);

}


/*
|--------------------------------------------------------------------------
| TEXT HELPER
|--------------------------------------------------------------------------
*/

function setText(id, value) {

    const element =
        document.getElementById(id);

    if (element) {

        element.textContent =
            value || "-";

    }

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

function getStatusLabel(status) {

    const labels = {

        belum_dikirim:
            "Not shipped",

        sedang_dikirim:
            "In transit",

        sudah_sampai:
            "Delivered"

    };


    return (
        labels[status] ||
        status ||
        "-"
    );

}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHTML(value) {

    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

}


/* =====================================================
   COURIER — DATABASE SHIPMENTS
===================================================== */

async function getCourierShipments() {

    try {

        const response = await fetch(
            "courier-shipments.php",
            {
                method: "GET",
                cache: "no-store"
            }
        );

        if (!response.ok) {
            throw new Error("Gagal mengambil shipment.");
        }

        const data = await response.json();

        return data.shipments || [];

    } catch (error) {

        console.error(error);

        showToast(
            "Gagal mengambil data shipment.",
            "error"
        );

        return [];
    }
}


/* =====================================================
   UPDATE STATUS SHIPMENT
===================================================== */

async function updateCourierShipment(shipmentId, action) {

    const formData = new FormData();

    formData.append("shipment_id", shipmentId);
    formData.append("action", action);

    try {

        const response = await fetch(
            "courier-update-shipment.php",
            {
                method: "POST",
                body: formData
            }
        );

        const data = await response.json();

        if (!data.success) {

            showToast(
                data.message || "Gagal mengubah status shipment.",
                "error"
            );

            return;
        }

        showToast(data.message);

        await renderCourierShipments();

    } catch (error) {

        console.error(error);

        showToast(
            "Gagal menghubungi server.",
            "error"
        );
    }
}


/* =====================================================
   RENDER COURIER
===================================================== */

window.renderCourierShipments = async function () {

    const container =
        document.getElementById(
            "courierShipmentList"
        );

    if (!container) return;

    const shipments =
        await getCourierShipments();

    container.innerHTML = "";

    if (!shipments.length) {

        container.innerHTML = `
            <div class="empty-state">
                <span>○</span>

                <strong>
                    No shipments available
                </strong>

                <p>
                    New customer shipments will appear here.
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


        let statusLabel =
            shipment.status;


        if (shipment.status === "pending") {
            statusLabel = "Pending";
        }

        if (shipment.status === "in_transit") {
            statusLabel = "In transit";
        }

        if (shipment.status === "delivered") {
            statusLabel = "Delivered";
        }


        let actionButton = "";


        /*
        |--------------------------------------------------------------------------
        | PENDING
        |--------------------------------------------------------------------------
        */

        if (shipment.status === "pending") {

            actionButton = `
                <button
                    type="button"
                    class="table-action"
                    data-courier-action="pickup"
                    data-shipment-id="${escapeHTML(
                        shipment.id
                    )}"
                >
                    Start pickup →
                </button>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | IN TRANSIT
        |--------------------------------------------------------------------------
        */

        else if (
            shipment.status === "in_transit"
        ) {

            actionButton = `
                <button
                    type="button"
                    class="table-action"
                    data-courier-action="delivered"
                    data-shipment-id="${escapeHTML(
                        shipment.id
                    )}"
                >
                    Mark delivered ✓
                </button>
            `;
        }


        /*
        |--------------------------------------------------------------------------
        | HTML
        |--------------------------------------------------------------------------
        */

        row.innerHTML = `

            <div class="shipment-main">

                <strong>
                    ${escapeHTML(
                        shipment.tracking_number
                    )}
                </strong>

                <span>
                    ${escapeHTML(
                        shipment.sender_name
                    )}
                </span>

            </div>


            <div class="shipment-recipient">

                <strong>
                    ${escapeHTML(
                        shipment.origin
                    )}
                    →
                    ${escapeHTML(
                        shipment.destination
                    )}
                </strong>

                <span>
                    ${escapeHTML(
                        shipment.receiver_name
                    )}
                </span>

            </div>


            <span
                class="status ${
                    shipment.status === "delivered"
                        ? "delivered"
                        : ""
                }"
            >
                ${escapeHTML(statusLabel)}
            </span>


            <div class="shipment-actions">

                <button
                    type="button"
                    class="table-action"
                    data-view-courier-shipment
                    data-shipment-id="${escapeHTML(
                        shipment.id
                    )}"
                >
                    View
                </button>

                ${actionButton}

            </div>

        `;


        container.appendChild(row);

    });

};


/* =====================================================
   COURIER BUTTON ACTION
===================================================== */

document.addEventListener(
    "click",
    event => {

        const button =
            event.target.closest(
                "[data-courier-action]"
            );

        if (!button) return;


        const shipmentId =
            button.dataset.shipmentId;

        const action =
            button.dataset.courierAction;


        if (!shipmentId || !action) {
            return;
        }


        let message;


        if (action === "pickup") {

            message =
                "Ambil shipment ini dan mulai perjalanan?";

        }

        else if (action === "delivered") {

            message =
                "Tandai shipment ini sebagai delivered?";

        }


        if (
            message &&
            !confirm(message)
        ) {
            return;
        }


        button.disabled = true;

        updateCourierShipment(
            shipmentId,
            action
        );
    }
);


/* =====================================================
   AUTO LOAD
===================================================== */

renderCourierShipments();


/* =====================================================
   COURIER — RENDER
===================================================== */

window.renderCourierShipments = async function () {

    const container =
        document.getElementById(
            "courierShipmentList"
        );

    if (!container) return;

    const shipments =
        await getCourierShipments();

    container.innerHTML = "";

    shipments.forEach(shipment => {

        const row =
            document.createElement("div");

        row.className = "shipment-row";

        row.innerHTML = `

            <div class="shipment-main">

                <strong>
                    ${escapeHTML(
                        shipment.tracking_number
                    )}
                </strong>

                <span>
                    ${escapeHTML(
                        shipment.sender_name
                    )}
                </span>

            </div>


            <div class="shipment-recipient">

                <strong>
                    ${escapeHTML(
                        shipment.origin
                    )}
                    →
                    ${escapeHTML(
                        shipment.destination
                    )}
                </strong>

                <span>
                    ${escapeHTML(
                        shipment.receiver_name
                    )}
                </span>

            </div>


            <span class="status">

                ${escapeHTML(
                    shipment.status
                )}

            </span>


            <div class="shipment-actions">

                <button
                    type="button"
                    class="table-action"
                    data-view-shipment="<?= htmlspecialchars($shipment['tracking_number'], ENT_QUOTES) ?>"
                >
                    View
                </button>

                <?php if ($shipment['status'] === 'pending'): ?>

                    <button
                        type="button"
                        class="table-action"
                        data-edit-shipment="<?= (int) $shipment['id'] ?>"
                        data-recipient="<?= htmlspecialchars($shipment['receiver_name'], ENT_QUOTES) ?>"
                        data-address="<?= htmlspecialchars($shipment['destination'], ENT_QUOTES) ?>"
                    >
                        Edit
                    </button>

                <?php endif; ?>

            </div>

        `;

        container.appendChild(row);

    });

};

renderCourierShipments();

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