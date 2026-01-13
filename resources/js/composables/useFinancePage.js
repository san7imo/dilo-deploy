import { computed, ref, watch } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";

const paymentMethodOptions = [
    { value: "transferencia", label: "Transferencia bancaria" },
    { value: "efectivo", label: "Efectivo" },
    { value: "tarjeta", label: "Tarjeta" },
    { value: "paypal", label: "PayPal" },
    { value: "otro", label: "Otro" },
];

const normalizeCurrency = (
    form,
    currencyField = "currency",
    rateField = "exchange_rate_to_base"
) => {
    const cur = (form[currencyField] || "").toUpperCase().trim();
    form[currencyField] = cur || "USD";

    if (form[currencyField] === "USD") {
        form[rateField] = 1;
    } else if (!form[rateField] || Number(form[rateField]) <= 0) {
        form[rateField] = 1;
    }
};

export function useFinancePage(props) {
    const { props: pageProps } = usePage();

    const roleNames = computed(() => pageProps.auth?.user?.role_names || []);
    const isAdmin = computed(() => roleNames.value.includes("admin"));
    const isRoadManager = computed(() =>
        roleNames.value.includes("roadmanager")
    );

    // UI State
    const activeTab = ref("resumen");
    const showPaymentModal = ref(false);
    const showExpenseModal = ref(false);
    const showArtistExpenseModal = ref(false);

    // Filtros (análisis)
    const filterType = ref("all");
    const filterYear = ref(null);
    const filterMonth = ref(null);
    const filterDateFrom = ref(null);
    const filterDateTo = ref(null);

    // Forms
    const paymentForm = useForm({
        payment_date: new Date().toISOString().slice(0, 10),
        amount_original: "",
        currency: "USD",
        exchange_rate_to_base: 1,
        payment_method: "",
        is_advance: false,
        notes: "",
    });

    const expenseForm = useForm({
        expense_date: new Date().toISOString().slice(0, 10),
        name: "",
        description: "",
        category: "",
        receipt_file: null,
        amount_original: "",
        currency: "USD",
        exchange_rate_to_base: 1,
    });

    const artistExpenseForm = useForm({
        artist_id: "",
        expense_date: new Date().toISOString().slice(0, 10),
        name: "",
        description: "",
        category: "",
        receipt_file: null,
        amount_original: "",
        currency: "USD",
        exchange_rate_to_base: 1,
        notes: "",
    });

    const statusForm = useForm({
        is_paid: props.event.is_paid ?? false,
    });

    const eventMetaForm = useForm({
        status: props.event.status || "",
        event_date: props.event.event_date || "",
        full_payment_due_date: props.event.full_payment_due_date || "",
    });

    const confirmForm = useForm({
        confirmed: true,
    });

    const roadManagers = computed(
        () => props.event.road_managers || props.event.roadManagers || []
    );

    const availableArtists = computed(() => {
        const artists = [];
        if (props.event.main_artist) {
            artists.push(props.event.main_artist);
        } else if (props.event.mainArtist) {
            artists.push(props.event.mainArtist);
        }
        return artists;
    });

    const currentRoadManager = computed(() => {
        if (!isRoadManager.value) return null;
        const userId = pageProps.auth?.user?.id;
        return roadManagers.value.find((rm) => rm.id === userId) || null;
    });

    const roadManagerConfirmedAt = computed(
        () => currentRoadManager.value?.pivot?.payment_confirmed_at || null
    );

    const roadManagerDue = computed(() => {
        const fee = Number(props.event.show_fee_total ?? 0);
        const advancePct = Number(props.event.advance_percentage ?? 0);
        const pct = Number.isFinite(advancePct) ? advancePct : 0;
        const normalizedPct = Math.min(Math.max(pct, 0), 100);
        const due = fee * (1 - normalizedPct / 100);
        return Number.isFinite(due) ? due : 0;
    });

    watch(
        isAdmin,
        (value) => {
            if (!value) {
                activeTab.value = "pagos";
            }
        },
        { immediate: true }
    );

    // Totales
    const totals = computed(() => {
        const paid = Number(props.finance?.total_paid_base ?? 0);
        const expenses = Number(props.finance?.total_expenses_base ?? 0);
        const artistPersonalExpenses = Number(
            props.finance?.artist_personal_expenses_base ?? 0
        );
        const net = paid - expenses;

        return {
            paid,
            expenses,
            artistPersonalExpenses,
            net,
            shareLabel: Number(
                props.finance?.label_share_estimated_base ?? net * 0.3
            ),
            shareArtist: Number(
                props.finance?.artist_share_estimated_base ?? net * 0.7
            ),
            shareArtistNet: Number(
                props.finance?.artist_net_share_base ??
                    net * 0.7 - artistPersonalExpenses
            ),
        };
    });

    const totalPaidBase = computed(() =>
        Number(props.finance?.total_paid_base ?? 0)
    );
    const showFeeTotal = computed(() =>
        Number(props.event.show_fee_total ?? 0)
    );
    const canMarkPaid = computed(
        () =>
            showFeeTotal.value > 0 && totalPaidBase.value >= showFeeTotal.value
    );
    const paidShortfall = computed(() => {
        if (showFeeTotal.value <= 0) return 0;
        return Math.max(showFeeTotal.value - totalPaidBase.value, 0);
    });
    const paidStatusAlert = computed(() => {
        if (showFeeTotal.value <= 0) {
            return "Define el fee del show para poder marcarlo como pagado.";
        }
        if (!canMarkPaid.value) {
            return `Faltan $${paidShortfall.value.toFixed(
                2
            )} USD para marcarlo como pagado.`;
        }
        return "";
    });

    const eventStatusValue = computed(() => {
        const raw = eventMetaForm.status || props.event.status || "";
        return raw.toString().trim().toLowerCase();
    });

    const eventStatusBadge = computed(() => {
        const status = eventStatusValue.value;
        if (!status) {
            return {
                label: "Sin estado",
                className:
                    "bg-gray-500/20 text-gray-300 border border-gray-500/30",
            };
        }

        const labels = {
            cotizado: "Cotizado",
            reservado: "Reservado",
            confirmado: "Confirmado",
            pospuesto: "Pospuesto",
            pagado: "Pagado",
            cancelado: "Cancelado",
        };

        const classes = {
            cotizado: "bg-gray-500/20 text-gray-200 border border-gray-500/30",
            reservado:
                "bg-yellow-500/20 text-yellow-300 border border-yellow-500/30",
            confirmado:
                "bg-blue-500/20 text-blue-300 border border-blue-500/30",
            pospuesto:
                "bg-orange-500/20 text-orange-300 border border-orange-500/30",
            pagado: "bg-green-500/20 text-green-300 border border-green-500/30",
            cancelado: "bg-red-500/20 text-red-300 border border-red-500/30",
        };

        return {
            label: labels[status] || status,
            className:
                classes[status] ||
                "bg-gray-500/20 text-gray-300 border border-gray-500/30",
        };
    });

    const statusAlert = computed(() => {
        const status = eventStatusValue.value;
        if (status === "pospuesto") {
            return {
                title: "Evento pospuesto",
                message:
                    "Actualiza la nueva fecha y confirma el plan con el equipo.",
                className:
                    "bg-orange-500/10 border border-orange-500/30 text-orange-200",
            };
        }
        if (status === "cancelado") {
            return {
                title: "Evento cancelado",
                message: "Revisa pagos y gastos para cerrar la contabilidad.",
                className:
                    "bg-red-500/10 border border-red-500/30 text-red-200",
            };
        }
        return null;
    });

    const eventArray = computed(() => {
        if (!props.event) return [];
        return [
            {
                ...props.event,
                title: props.event.title || props.event.name || "Evento",
                event_date: props.event.event_date || new Date().toISOString(),
                status: props.event.is_paid ? "pagado" : "pendiente",
                total_paid_base: props.finance?.total_paid_base ?? 0,
                total_expenses_base: props.finance?.total_expenses_base ?? 0,
                net_base:
                    (props.finance?.total_paid_base ?? 0) -
                    (props.finance?.total_expenses_base ?? 0),
            },
        ];
    });

    const normalizePaymentCurrencyAndRate = () =>
        normalizeCurrency(paymentForm, "currency", "exchange_rate_to_base");
    const normalizeExpenseCurrencyAndRate = () =>
        normalizeCurrency(expenseForm, "currency", "exchange_rate_to_base");
    const normalizeArtistExpenseCurrencyAndRate = () =>
        normalizeCurrency(
            artistExpenseForm,
            "currency",
            "exchange_rate_to_base"
        );

    const openPaymentModal = () => {
        paymentForm.payment_date = new Date().toISOString().slice(0, 10);
        paymentForm.currency =
            (paymentForm.currency || "USD").toUpperCase().trim() || "USD";
        if (paymentForm.currency === "USD")
            paymentForm.exchange_rate_to_base = 1;
        showPaymentModal.value = true;
    };

    const openExpenseModal = () => {
        expenseForm.expense_date = new Date().toISOString().slice(0, 10);
        expenseForm.currency =
            (expenseForm.currency || "USD").toUpperCase().trim() || "USD";
        if (expenseForm.currency === "USD")
            expenseForm.exchange_rate_to_base = 1;
        showExpenseModal.value = true;
    };

    const openArtistExpenseModal = () => {
        artistExpenseForm.reset();
        artistExpenseForm.expense_date = new Date().toISOString().slice(0, 10);
        artistExpenseForm.currency = "USD";
        artistExpenseForm.exchange_rate_to_base = 1;
        if (availableArtists.value.length > 0) {
            artistExpenseForm.artist_id = availableArtists.value[0].id;
        }
        showArtistExpenseModal.value = true;
    };

    const submitPayment = () => {
        normalizePaymentCurrencyAndRate();

        paymentForm.post(route("admin.events.payments.store", props.event.id), {
            preserveScroll: true,
            onSuccess: () => {
                paymentForm.reset(
                    "amount_original",
                    "payment_method",
                    "is_advance",
                    "notes"
                );
                showPaymentModal.value = false;
                activeTab.value = "pagos";
            },
        });
    };

    const deletePayment = (paymentId) => {
        if (!isAdmin.value) return;
        if (!confirm("¿Eliminar este pago?")) return;

        paymentForm.delete(route("admin.events.payments.destroy", paymentId), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route("admin.events.finance", props.event.id), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    };

    const submitExpense = () => {
        normalizeExpenseCurrencyAndRate();

        expenseForm.post(route("admin.events.expenses.store", props.event.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                expenseForm.reset(
                    "amount_original",
                    "name",
                    "description",
                    "category",
                    "receipt_file"
                );
                showExpenseModal.value = false;
                activeTab.value = "gastos";
                router.visit(route("admin.events.finance", props.event.id), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    };

    const deleteExpense = (expenseId) => {
        if (!isAdmin.value) return;
        if (!confirm("¿Eliminar este gasto?")) return;

        expenseForm.delete(route("admin.events.expenses.destroy", expenseId), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route("admin.events.finance", props.event.id), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
        });
    };

    const updatePaymentStatus = () => {
        statusForm.patch(
            route("admin.events.payment-status.update", props.event.id),
            {
                preserveScroll: true,
            }
        );
    };

    const updateEventDetails = () => {
        eventMetaForm.patch(
            route("admin.events.details.update", props.event.id),
            {
                preserveScroll: true,
            }
        );
    };

    const confirmRoadManagerPayment = () => {
        if (confirmForm.processing) return;
        if (roadManagerConfirmedAt.value) return;

        confirmForm.patch(
            route("admin.events.roadmanager-payment.update", props.event.id),
            {
                preserveScroll: true,
            }
        );
    };

    const statusBadge = computed(() => {
        const paid = !!statusForm.is_paid;
        return {
            label: paid ? "Pagado" : "Pendiente",
            className: paid
                ? "bg-green-500/20 text-green-300 border border-green-500/40"
                : "bg-yellow-500/20 text-yellow-300 border border-yellow-500/40",
        };
    });

    const canSubmitPaymentStatus = computed(
        () => !statusForm.is_paid || canMarkPaid.value
    );
    const canSubmitEventDetails = computed(
        () => eventStatusValue.value !== "pagado" || canMarkPaid.value
    );

    const submitArtistExpense = () => {
        normalizeArtistExpenseCurrencyAndRate();

        artistExpenseForm.post(
            route("admin.events.artist-expenses.store", props.event.id),
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    artistExpenseForm.reset();
                    showArtistExpenseModal.value = false;
                    activeTab.value = "gastos-artista";
                },
            }
        );
    };

    const approveArtistExpense = (expenseId) => {
        if (!isAdmin.value) return;
        if (!confirm("¿Aprobar este gasto personal del artista?")) return;

        router.patch(
            route("admin.artist-expenses.approve", expenseId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ["event"] });
                },
            }
        );
    };

    const deleteArtistExpense = (expenseId) => {
        if (!confirm("¿Eliminar este gasto personal del artista?")) return;

        router.delete(route("admin.artist-expenses.destroy", expenseId), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ["event"] });
            },
        });
    };

    return {
        //roles y permisos
        roleNames,
        isAdmin,
        isRoadManager,

        // estados 
        activeTab,
        showPaymentModal,
        showExpenseModal,
        showArtistExpenseModal,

        // filtros
        filterType,
        filterYear,
        filterMonth,
        filterDateFrom,
        filterDateTo,

        // opciones
        paymentMethodOptions,

        // formularios
        paymentForm,
        expenseForm,
        artistExpenseForm,
        statusForm,
        eventMetaForm,
        confirmForm,

        // computed data
        roadManagers,
        availableArtists,
        currentRoadManager,
        roadManagerConfirmedAt,
        roadManagerDue,
        totals,
        totalPaidBase,
        showFeeTotal,
        canMarkPaid,
        paidShortfall,
        paidStatusAlert,
        eventStatusValue,
        eventStatusBadge,
        statusAlert,
        eventArray,
        statusBadge,
        canSubmitPaymentStatus,
        canSubmitEventDetails,

        // normalizadores
        normalizePaymentCurrencyAndRate,
        normalizeExpenseCurrencyAndRate,
        normalizeArtistExpenseCurrencyAndRate,

        // acciones
        openPaymentModal,
        openExpenseModal,
        openArtistExpenseModal,
        submitPayment,
        deletePayment,
        submitExpense,
        deleteExpense,
        updatePaymentStatus,
        updateEventDetails,
        confirmRoadManagerPayment,
        submitArtistExpense,
        approveArtistExpense,
        deleteArtistExpense,
    };
}

export default useFinancePage;
