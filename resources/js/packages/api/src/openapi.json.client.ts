import { makeApi, Zodios, type ZodiosOptions } from '@zodios/core';
import { z } from 'zod';

const ApiTokenResource = z
    .object({
        id: z.string(),
        name: z.string(),
        revoked: z.boolean(),
        scopes: z.array(z.string()),
        created_at: z.string(),
        expires_at: z.union([z.string(), z.null()]),
    })
    .passthrough();
const ApiTokenCollection = z.array(ApiTokenResource);
const ApiTokenStoreRequest = z.object({ name: z.string().min(1).max(255) }).passthrough();
const ApiTokenWithAccessTokenResource = z
    .object({
        id: z.string(),
        name: z.string(),
        revoked: z.boolean(),
        scopes: z.array(z.string()),
        created_at: z.string(),
        expires_at: z.union([z.string(), z.null()]),
        access_token: z.string(),
    })
    .passthrough();
const ClientResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_archived: z.boolean(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const ClientCollection = z.array(ClientResource);
const ClientStoreRequest = z.object({ name: z.string().min(1).max(255) }).passthrough();
const ClientUpdateRequest = z
    .object({ name: z.string().min(1).max(255), is_archived: z.boolean().optional() })
    .passthrough();
const ImportRequest = z.object({ type: z.string(), data: z.string() }).passthrough();
const InvitationResource = z
    .object({ id: z.string(), email: z.string(), role: z.string() })
    .passthrough();
const InvitationStoreRequest = z
    .object({ email: z.string().email(), role: z.enum(['admin', 'manager', 'employee']) })
    .passthrough();
const InvoiceResource = z
    .object({
        id: z.string(),
        organization_id: z.string(),
        reference: z.string(),
        seller_name: z.string(),
        buyer_name: z.string(),
        status: z.string(),
        date: z.string(),
        due_at: z.string(),
        paid_date: z.string(),
        created_at: z.union([z.string(), z.null()]),
        updated_at: z.union([z.string(), z.null()]),
    })
    .passthrough();
const InvoiceCollection = z.array(InvoiceResource);
const InvoiceDiscountType = z.enum(['percentage', 'fixed']);
const InvoiceStoreRequest = z
    .object({
        due_at: z.union([z.string(), z.null()]).optional(),
        paid_date: z.union([z.string(), z.null()]).optional(),
        seller_name: z.string(),
        seller_vatin: z.union([z.string(), z.null()]).optional(),
        seller_address_line_1: z.union([z.string(), z.null()]).optional(),
        seller_address_line_2: z.union([z.string(), z.null()]).optional(),
        seller_address_line_3: z.union([z.string(), z.null()]).optional(),
        seller_address_post_code: z.union([z.string(), z.null()]).optional(),
        seller_address_city: z.union([z.string(), z.null()]).optional(),
        seller_address_country: z.union([z.string(), z.null()]).optional(),
        seller_phone: z.union([z.string(), z.null()]).optional(),
        seller_email: z.union([z.string(), z.null()]).optional(),
        buyer_name: z.string(),
        buyer_vatin: z.union([z.string(), z.null()]).optional(),
        buyer_address_line_1: z.union([z.string(), z.null()]).optional(),
        buyer_address_line_2: z.union([z.string(), z.null()]).optional(),
        buyer_address_line_3: z.union([z.string(), z.null()]).optional(),
        buyer_address_post_code: z.union([z.string(), z.null()]).optional(),
        buyer_address_city: z.union([z.string(), z.null()]).optional(),
        buyer_address_country: z.union([z.string(), z.null()]).optional(),
        buyer_phone: z.union([z.string(), z.null()]).optional(),
        buyer_email: z.union([z.string(), z.null()]).optional(),
        date: z.string(),
        billing_period_start: z.union([z.string(), z.null()]).optional(),
        billing_period_end: z.union([z.string(), z.null()]).optional(),
        reference: z.string(),
        currency: z.string(),
        payment_iban: z.union([z.string(), z.null()]).optional(),
        tax_rate: z.number().int().gte(0).lte(2147483647).optional(),
        discount_amount: z.number().int().gte(0).lte(9223372036854776000).optional(),
        discount_type: InvoiceDiscountType.optional(),
        footer: z.union([z.string(), z.null()]).optional(),
        notes: z.union([z.string(), z.null()]).optional(),
        payment_terms: z.union([z.string(), z.null()]).optional(),
        is_eu_reverse_charge: z.boolean().optional(),
        entries: z
            .array(
                z
                    .object({
                        name: z.string(),
                        description: z.union([z.string(), z.null()]).optional(),
                        unit_price: z.number().int().gte(0).lte(9223372036854776000),
                        quantity: z.number().gte(0).lte(99999999),
                    })
                    .passthrough()
            )
            .optional(),
    })
    .passthrough();
const InvoiceEntryResource = z
    .object({
        id: z.string(),
        invoice_id: z.string(),
        name: z.string(),
        description: z.string(),
        unit_price: z.string(),
        quantity: z.number(),
        order_index: z.string(),
        created_at: z.union([z.string(), z.null()]),
        updated_at: z.union([z.string(), z.null()]),
    })
    .passthrough();
const DetailedInvoiceResource = z
    .object({
        id: z.string(),
        organization_id: z.string(),
        reference: z.string(),
        seller_name: z.string(),
        seller_vatin: z.string(),
        seller_address_line_1: z.string(),
        seller_address_line_2: z.string(),
        seller_address_line_3: z.string(),
        seller_address_post_code: z.string(),
        seller_address_city: z.string(),
        seller_address_country: z.string(),
        seller_phone: z.string(),
        seller_email: z.string(),
        buyer_name: z.string(),
        buyer_vatin: z.string(),
        buyer_address_line_1: z.string(),
        buyer_address_line_2: z.string(),
        buyer_address_line_3: z.string(),
        buyer_address_post_code: z.string(),
        buyer_address_city: z.string(),
        buyer_address_country: z.string(),
        buyer_phone: z.string(),
        buyer_email: z.string(),
        paid_date: z.string(),
        due_at: z.string(),
        discount_type: z.string(),
        discount_amount: z.number().int(),
        tax_rate: z.number().int(),
        payment_iban: z.string(),
        status: z.string(),
        currency: z.string(),
        date: z.string(),
        footer: z.string(),
        notes: z.string(),
        payment_terms: z.string(),
        is_eu_reverse_charge: z.string(),
        billing_period_start: z.string(),
        billing_period_end: z.string(),
        created_at: z.union([z.string(), z.null()]),
        updated_at: z.union([z.string(), z.null()]),
        entries: z.array(InvoiceEntryResource),
    })
    .passthrough();
const InvoiceStatus = z.enum(['draft', 'sent', 'cancelled']);
const InvoiceUpdateRequest = z
    .object({
        status: InvoiceStatus,
        due_at: z.union([z.string(), z.null()]),
        paid_date: z.union([z.string(), z.null()]),
        seller_name: z.string(),
        seller_vatin: z.union([z.string(), z.null()]),
        seller_address_line_1: z.union([z.string(), z.null()]),
        seller_address_line_2: z.union([z.string(), z.null()]),
        seller_address_line_3: z.union([z.string(), z.null()]),
        seller_address_post_code: z.union([z.string(), z.null()]),
        seller_address_city: z.union([z.string(), z.null()]),
        seller_address_country: z.union([z.string(), z.null()]),
        seller_phone: z.union([z.string(), z.null()]),
        seller_email: z.union([z.string(), z.null()]),
        buyer_name: z.string(),
        buyer_vatin: z.union([z.string(), z.null()]),
        buyer_address_line_1: z.union([z.string(), z.null()]),
        buyer_address_line_2: z.union([z.string(), z.null()]),
        buyer_address_line_3: z.union([z.string(), z.null()]),
        buyer_address_post_code: z.union([z.string(), z.null()]),
        buyer_address_city: z.union([z.string(), z.null()]),
        buyer_address_country: z.union([z.string(), z.null()]),
        buyer_phone: z.union([z.string(), z.null()]),
        buyer_email: z.union([z.string(), z.null()]),
        date: z.string(),
        billing_period_start: z.union([z.string(), z.null()]),
        billing_period_end: z.union([z.string(), z.null()]),
        reference: z.string(),
        currency: z.string(),
        payment_iban: z.union([z.string(), z.null()]),
        tax_rate: z.number().int().gte(0).lte(2147483647),
        discount_amount: z.number().int().gte(0).lte(9223372036854776000),
        discount_type: InvoiceDiscountType,
        footer: z.union([z.string(), z.null()]),
        notes: z.union([z.string(), z.null()]),
        payment_terms: z.union([z.string(), z.null()]),
        is_eu_reverse_charge: z.boolean(),
        entries: z.array(
            z
                .object({
                    id: z.union([z.string(), z.null()]).optional(),
                    name: z.string(),
                    description: z.union([z.string(), z.null()]).optional(),
                    unit_price: z.number().int().gte(0).lte(9223372036854776000),
                    quantity: z.number().gte(0).lte(99999999),
                })
                .passthrough()
        ),
    })
    .partial()
    .passthrough();
const InvoiceDownloadRequest = z.object({ with_e_invoice: z.boolean() }).passthrough();
const InvoiceSettingResource = z
    .object({
        seller_name: z.union([z.string(), z.null()]),
        seller_vatin: z.union([z.string(), z.null()]),
        seller_address_line_1: z.union([z.string(), z.null()]),
        seller_address_line_2: z.union([z.string(), z.null()]),
        seller_address_line_3: z.union([z.string(), z.null()]),
        seller_address_post_code: z.union([z.string(), z.null()]),
        seller_address_city: z.union([z.string(), z.null()]),
        seller_address_country: z.union([z.string(), z.null()]),
        seller_phone: z.union([z.string(), z.null()]),
        seller_email: z.union([z.string(), z.null()]),
        footer_default: z.union([z.string(), z.null()]),
        notes_default: z.union([z.string(), z.null()]),
        tax_rate_default: z.union([z.number(), z.null()]),
        e_invoicing_enabled: z.boolean(),
        organization_id: z.string(),
    })
    .passthrough();
const InvoiceSettingUpdateRequest = z
    .object({
        seller_name: z.union([z.string(), z.null()]),
        seller_vatin: z.union([z.string(), z.null()]),
        seller_address_line_1: z.union([z.string(), z.null()]),
        seller_address_line_2: z.union([z.string(), z.null()]),
        seller_address_line_3: z.union([z.string(), z.null()]),
        seller_address_post_code: z.union([z.string(), z.null()]),
        seller_address_city: z.union([z.string(), z.null()]),
        seller_address_country: z.union([z.string(), z.null()]),
        seller_phone: z.union([z.string(), z.null()]),
        seller_email: z.union([z.string(), z.null()]),
        footer_default: z.union([z.string(), z.null()]),
        notes_default: z.union([z.string(), z.null()]),
        tax_rate_default: z.union([z.number(), z.null()]),
        e_invoicing_enabled: z.boolean(),
    })
    .partial()
    .passthrough();
const MemberResource = z
    .object({
        id: z.string(),
        user_id: z.string(),
        name: z.string(),
        email: z.string(),
        role: z.string(),
        is_placeholder: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const Role = z.enum(['owner', 'admin', 'manager', 'employee', 'placeholder']);
const MemberUpdateRequest = z
    .object({ role: Role, billable_rate: z.union([z.number(), z.null()]) })
    .partial()
    .passthrough();
const MemberMergeIntoRequest = z.object({ member_id: z.string() }).partial().passthrough();
const NumberFormat = z.enum([
    'point-comma',
    'comma-point',
    'space-comma',
    'space-point',
    'apostrophe-point',
]);
const CurrencyFormat = z.enum([
    'iso-code-before-with-space',
    'iso-code-after-with-space',
    'symbol-before',
    'symbol-after',
    'symbol-before-with-space',
    'symbol-after-with-space',
]);
const DateFormat = z.enum([
    'point-separated-d-m-yyyy',
    'slash-separated-mm-dd-yyyy',
    'slash-separated-dd-mm-yyyy',
    'hyphen-separated-dd-mm-yyyy',
    'hyphen-separated-mm-dd-yyyy',
    'hyphen-separated-yyyy-mm-dd',
]);
const IntervalFormat = z.enum([
    'decimal',
    'hours-minutes',
    'hours-minutes-colon-separated',
    'hours-minutes-seconds-colon-separated',
]);
const TimeFormat = z.enum(['12-hours', '24-hours']);
const OrganizationResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_personal: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
        employees_can_see_billable_rates: z.boolean(),
        currency: z.string(),
        currency_symbol: z.string(),
        number_format: NumberFormat,
        currency_format: CurrencyFormat,
        date_format: DateFormat,
        interval_format: IntervalFormat,
        time_format: TimeFormat,
    })
    .passthrough();
const OrganizationUpdateRequest = z
    .object({
        name: z.string().max(255),
        billable_rate: z.union([z.number(), z.null()]),
        employees_can_see_billable_rates: z.boolean(),
        number_format: NumberFormat,
        currency_format: CurrencyFormat,
        date_format: DateFormat,
        interval_format: IntervalFormat,
        time_format: TimeFormat,
    })
    .partial()
    .passthrough();
const ProjectResource = z
    .object({
        id: z.string(),
        name: z.string(),
        color: z.string(),
        client_id: z.union([z.string(), z.null()]),
        is_archived: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
        is_billable: z.boolean(),
        estimated_time: z.union([z.number(), z.null()]),
        spent_time: z.number().int(),
        is_public: z.boolean(),
    })
    .passthrough();
const ProjectStoreRequest = z
    .object({
        name: z.string().min(1).max(255),
        color: z.string().max(255),
        is_billable: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        client_id: z.union([z.string(), z.null()]).optional(),
        estimated_time: z.union([z.number(), z.null()]).optional(),
        is_public: z.boolean().optional(),
    })
    .passthrough();
const ProjectUpdateRequest = z
    .object({
        name: z.string().max(255),
        color: z.string().max(255),
        is_billable: z.boolean(),
        is_archived: z.boolean().optional(),
        is_public: z.boolean().optional(),
        client_id: z.union([z.string(), z.null()]).optional(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        estimated_time: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const ProjectMemberResource = z
    .object({
        id: z.string(),
        billable_rate: z.union([z.number(), z.null()]),
        member_id: z.string(),
        project_id: z.string(),
    })
    .passthrough();
const ProjectMemberStoreRequest = z
    .object({ member_id: z.string(), billable_rate: z.union([z.number(), z.null()]).optional() })
    .passthrough();
const ProjectMemberUpdateRequest = z
    .object({ billable_rate: z.union([z.number(), z.null()]) })
    .partial()
    .passthrough();
const ReportResource = z
    .object({
        id: z.string(),
        name: z.string(),
        description: z.union([z.string(), z.null()]),
        is_public: z.boolean(),
        public_until: z.union([z.string(), z.null()]),
        shareable_link: z.union([z.string(), z.null()]),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const TimeEntryAggregationType = z.enum([
    'day',
    'week',
    'month',
    'year',
    'user',
    'project',
    'task',
    'client',
    'billable',
    'description',
    'tag',
]);
const TimeEntryAggregationTypeInterval = z.enum(['day', 'week', 'month', 'year']);
const Weekday = z.enum([
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
]);
const TimeEntryRoundingType = z.enum(['up', 'down', 'nearest']);
const ReportStoreRequest = z
    .object({
        name: z.string().max(255),
        description: z.union([z.string(), z.null()]).optional(),
        is_public: z.boolean(),
        public_until: z.union([z.string(), z.null()]).optional(),
        properties: z
            .object({
                start: z.string(),
                end: z.string(),
                active: z.union([z.boolean(), z.null()]).optional(),
                member_ids: z.union([z.array(z.string().uuid()), z.null()]).optional(),
                billable: z.union([z.boolean(), z.null()]).optional(),
                client_ids: z.union([z.array(z.string().uuid()), z.null()]).optional(),
                project_ids: z.union([z.array(z.string().uuid()), z.null()]).optional(),
                tag_ids: z.union([z.array(z.string().uuid()), z.null()]).optional(),
                task_ids: z.union([z.array(z.string().uuid()), z.null()]).optional(),
                group: TimeEntryAggregationType,
                sub_group: TimeEntryAggregationType,
                history_group: TimeEntryAggregationTypeInterval,
                week_start: Weekday.optional(),
                timezone: z.union([z.string(), z.null()]).optional(),
                rounding_type: TimeEntryRoundingType.optional(),
                rounding_minutes: z.union([z.number(), z.null()]).optional(),
            })
            .passthrough(),
    })
    .passthrough();
const DetailedReportResource = z
    .object({
        id: z.string(),
        name: z.string(),
        description: z.union([z.string(), z.null()]),
        is_public: z.boolean(),
        public_until: z.union([z.string(), z.null()]),
        shareable_link: z.union([z.string(), z.null()]),
        properties: z
            .object({
                group: z.string(),
                sub_group: z.string(),
                history_group: z.string(),
                start: z.string(),
                end: z.string(),
                active: z.union([z.boolean(), z.null()]),
                member_ids: z.union([z.array(z.string()), z.null()]),
                billable: z.union([z.boolean(), z.null()]),
                client_ids: z.union([z.array(z.string()), z.null()]),
                project_ids: z.union([z.array(z.string()), z.null()]),
                tag_ids: z.union([z.array(z.string()), z.null()]),
                task_ids: z.union([z.array(z.string()), z.null()]),
                rounding_type: z.union([z.string(), z.null()]),
                rounding_minutes: z.union([z.number(), z.null()]),
            })
            .passthrough(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const ReportUpdateRequest = z
    .object({
        name: z.string().max(255),
        description: z.union([z.string(), z.null()]),
        is_public: z.boolean(),
        public_until: z.union([z.string(), z.null()]),
    })
    .partial()
    .passthrough();
const DetailedWithDataReportResource = z
    .object({
        name: z.string(),
        description: z.union([z.string(), z.null()]),
        public_until: z.union([z.string(), z.null()]),
        currency: z.string(),
        number_format: NumberFormat,
        currency_format: CurrencyFormat,
        currency_symbol: z.string(),
        date_format: DateFormat,
        interval_format: IntervalFormat,
        time_format: TimeFormat,
        properties: z
            .object({
                group: z.string(),
                sub_group: z.string(),
                history_group: z.string(),
                start: z.string(),
                end: z.string(),
            })
            .passthrough(),
        data: z
            .object({
                grouped_type: z.union([z.string(), z.null()]),
                grouped_data: z.union([
                    z.array(
                        z
                            .object({
                                key: z.union([z.string(), z.null()]),
                                description: z.union([z.string(), z.null()]),
                                color: z.union([z.string(), z.null()]),
                                seconds: z.number().int(),
                                cost: z.number().int(),
                                grouped_type: z.union([z.string(), z.null()]),
                                grouped_data: z.union([
                                    z.array(
                                        z
                                            .object({
                                                key: z.union([z.string(), z.null()]),
                                                description: z.union([z.string(), z.null()]),
                                                color: z.union([z.string(), z.null()]),
                                                seconds: z.number().int(),
                                                cost: z.number().int(),
                                                grouped_type: z.null(),
                                                grouped_data: z.null(),
                                            })
                                            .passthrough()
                                    ),
                                    z.null(),
                                ]),
                            })
                            .passthrough()
                    ),
                    z.null(),
                ]),
                seconds: z.number().int(),
                cost: z.number().int(),
            })
            .passthrough(),
        history_data: z
            .object({
                grouped_type: z.union([z.string(), z.null()]),
                grouped_data: z.union([
                    z.array(
                        z
                            .object({
                                key: z.union([z.string(), z.null()]),
                                description: z.union([z.string(), z.null()]),
                                seconds: z.number().int(),
                                cost: z.number().int(),
                                grouped_type: z.union([z.string(), z.null()]),
                                grouped_data: z.union([
                                    z.array(
                                        z
                                            .object({
                                                key: z.union([z.string(), z.null()]),
                                                description: z.union([z.string(), z.null()]),
                                                seconds: z.number().int(),
                                                cost: z.number().int(),
                                                grouped_type: z.null(),
                                                grouped_data: z.null(),
                                            })
                                            .passthrough()
                                    ),
                                    z.null(),
                                ]),
                            })
                            .passthrough()
                    ),
                    z.null(),
                ]),
                seconds: z.number().int(),
                cost: z.number().int(),
            })
            .passthrough(),
    })
    .passthrough();
const TagResource = z
    .object({ id: z.string(), name: z.string(), created_at: z.string(), updated_at: z.string() })
    .passthrough();
const TagCollection = z.array(TagResource);
const TagStoreRequest = z.object({ name: z.string().min(1).max(255) }).passthrough();
const TagUpdateRequest = z.object({ name: z.string().min(1).max(255) }).passthrough();
const TaskResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_done: z.boolean(),
        project_id: z.string(),
        estimated_time: z.union([z.number(), z.null()]),
        spent_time: z.number().int(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const TaskStoreRequest = z
    .object({
        name: z.string().min(1).max(255),
        project_id: z.string(),
        estimated_time: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const TaskUpdateRequest = z
    .object({
        name: z.string().min(1).max(255),
        is_done: z.boolean().optional(),
        estimated_time: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const start = z.union([z.string(), z.null()]).optional();
const rounding_minutes = z.union([z.number(), z.null()]).optional();
const TimeEntryResource = z
    .object({
        id: z.string(),
        start: z.string(),
        end: z.union([z.string(), z.null()]),
        duration: z.union([z.number(), z.null()]),
        description: z.union([z.string(), z.null()]),
        task_id: z.union([z.string(), z.null()]),
        project_id: z.union([z.string(), z.null()]),
        organization_id: z.string(),
        user_id: z.string(),
        tags: z.array(z.string()),
        billable: z.boolean(),
    })
    .passthrough();
const TimeEntryStoreRequest = z
    .object({
        member_id: z.string(),
        project_id: z.union([z.string(), z.null()]).optional(),
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        billable: z.boolean(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();
const TimeEntryUpdateMultipleRequest = z
    .object({
        ids: z.array(z.string().uuid()),
        changes: z
            .object({
                member_id: z.string(),
                project_id: z.union([z.string(), z.null()]),
                task_id: z.union([z.string(), z.null()]),
                billable: z.boolean(),
                description: z.union([z.string(), z.null()]),
                tags: z.union([z.array(z.string()), z.null()]),
            })
            .partial()
            .passthrough(),
    })
    .passthrough();
const TimeEntryUpdateRequest = z
    .object({
        member_id: z.string(),
        project_id: z.union([z.string(), z.null()]),
        task_id: z.union([z.string(), z.null()]),
        start: z.string(),
        end: z.union([z.string(), z.null()]),
        billable: z.boolean(),
        description: z.union([z.string(), z.null()]),
        tags: z.union([z.array(z.string()), z.null()]),
    })
    .partial()
    .passthrough();
const UserResource = z
    .object({
        id: z.string(),
        name: z.string(),
        email: z.string(),
        profile_photo_url: z.string(),
        timezone: z.string(),
        week_start: Weekday,
    })
    .passthrough();
const PersonalMembershipResource = z
    .object({
        id: z.string(),
        organization: z
            .object({ id: z.string(), name: z.string(), currency: z.string() })
            .passthrough(),
        role: z.string(),
    })
    .passthrough();

export const schemas = {
    ApiTokenResource,
    ApiTokenCollection,
    ApiTokenStoreRequest,
    ApiTokenWithAccessTokenResource,
    ClientResource,
    ClientCollection,
    ClientStoreRequest,
    ClientUpdateRequest,
    ImportRequest,
    InvitationResource,
    InvitationStoreRequest,
    InvoiceResource,
    InvoiceCollection,
    InvoiceDiscountType,
    InvoiceStoreRequest,
    InvoiceEntryResource,
    DetailedInvoiceResource,
    InvoiceStatus,
    InvoiceUpdateRequest,
    InvoiceDownloadRequest,
    InvoiceSettingResource,
    InvoiceSettingUpdateRequest,
    MemberResource,
    Role,
    MemberUpdateRequest,
    MemberMergeIntoRequest,
    NumberFormat,
    CurrencyFormat,
    DateFormat,
    IntervalFormat,
    TimeFormat,
    OrganizationResource,
    OrganizationUpdateRequest,
    ProjectResource,
    ProjectStoreRequest,
    ProjectUpdateRequest,
    ProjectMemberResource,
    ProjectMemberStoreRequest,
    ProjectMemberUpdateRequest,
    ReportResource,
    TimeEntryAggregationType,
    TimeEntryAggregationTypeInterval,
    Weekday,
    TimeEntryRoundingType,
    ReportStoreRequest,
    DetailedReportResource,
    ReportUpdateRequest,
    DetailedWithDataReportResource,
    TagResource,
    TagCollection,
    TagStoreRequest,
    TagUpdateRequest,
    TaskResource,
    TaskStoreRequest,
    TaskUpdateRequest,
    start,
    rounding_minutes,
    TimeEntryResource,
    TimeEntryStoreRequest,
    TimeEntryUpdateMultipleRequest,
    TimeEntryUpdateRequest,
    UserResource,
    PersonalMembershipResource,
};

const endpoints = makeApi([
    {
        method: 'get',
        path: '/v1/countries',
        alias: 'getCountries',
        requestFormat: 'json',
        response: z.array(z.object({ code: z.string(), name: z.string() }).passthrough()),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/currencies',
        alias: 'getCurrencies',
        requestFormat: 'json',
        response: z.array(
            z.object({ code: z.string(), name: z.string(), symbol: z.string() }).passthrough()
        ),
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization',
        alias: 'getOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization',
        alias: 'updateOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: OrganizationUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/daily-tracked-hours',
        alias: 'dailyTrackedHours',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(z.object({ date: z.string(), duration: z.number().int() }).passthrough()),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/last-seven-days',
        alias: 'lastSevenDays',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(
            z
                .object({
                    date: z.string(),
                    duration: z.number().int(),
                    history: z.array(z.number().int()),
                })
                .passthrough()
        ),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/latest-tasks',
        alias: 'latestTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(
            z
                .object({
                    task_id: z.string(),
                    name: z.string(),
                    description: z.union([z.string(), z.null()]),
                    status: z.boolean(),
                    time_entry_id: z.union([z.string(), z.null()]),
                })
                .passthrough()
        ),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/latest-team-activity',
        alias: 'latestTeamActivity',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(
            z
                .object({
                    member_id: z.string(),
                    name: z.string(),
                    description: z.union([z.string(), z.null()]),
                    time_entry_id: z.string(),
                    task_id: z.union([z.string(), z.null()]),
                    status: z.boolean(),
                })
                .passthrough()
        ),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-billable-amount',
        alias: 'totalWeeklyBillableAmount',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ value: z.number().int(), currency: z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-billable-time',
        alias: 'totalWeeklyBillableTime',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.number().int(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/total-weekly-time',
        alias: 'totalWeeklyTime',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.number().int(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/weekly-history',
        alias: 'weeklyHistory',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(z.object({ date: z.string(), duration: z.number().int() }).passthrough()),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/charts/weekly-project-overview',
        alias: 'weeklyProjectOverview',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.array(
            z.object({ value: z.number().int(), name: z.string(), color: z.string() }).passthrough()
        ),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/clients',
        alias: 'getClients',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: z.number().int().gte(1).lte(2147483647).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z.object({ data: ClientCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/clients',
        alias: 'createClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'updateClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ClientUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'deleteClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/export',
        alias: 'exportOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ success: z.boolean(), download_url: z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/import',
        alias: 'importData',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ImportRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                report: z
                    .object({
                        clients: z.object({ created: z.number().int() }).passthrough(),
                        projects: z.object({ created: z.number().int() }).passthrough(),
                        tasks: z.object({ created: z.number().int() }).passthrough(),
                        time_entries: z.object({ created: z.number().int() }).passthrough(),
                        tags: z.object({ created: z.number().int() }).passthrough(),
                        users: z.object({ created: z.number().int() }).passthrough(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 400,
                schema: z.union([
                    z.object({ message: z.string() }).passthrough(),
                    z.object({ message: z.literal('Invalid base64 encoded data') }).passthrough(),
                ]),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/importers',
        alias: 'getImporters',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(
                    z
                        .object({ key: z.string(), name: z.string(), description: z.string() })
                        .passthrough()
                ),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invitations',
        alias: 'getInvitations',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(InvitationResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations',
        alias: 'invite',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvitationStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/invitations/:invitation',
        alias: 'removeInvitation',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations/:invitation/resend',
        alias: 'resendInvitationEmail',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoice-settings',
        alias: 'getInvoiceSettings',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: InvoiceSettingResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/invoice-settings',
        alias: 'updateInvoiceSettings',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceSettingUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: InvoiceSettingResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoices',
        alias: 'getInvoices',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: z.number().int().gte(1).lte(2147483647).optional(),
            },
        ],
        response: z.object({ data: InvoiceCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices',
        alias: 'createInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'getInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: z.number().int(),
            },
        ],
        response: z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'updateInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: InvoiceUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: z.number().int(),
            },
        ],
        response: z.object({ data: DetailedInvoiceResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/invoices/:invoice',
        alias: 'deleteInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: z.number().int(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices/:invoice/download',
        alias: 'downloadInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ with_e_invoice: z.boolean() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: z.number().int(),
            },
        ],
        response: z.object({ download_link: z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invoices/:invoice/download-e-invoice',
        alias: 'downloadEInvoice',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invoice',
                type: 'Path',
                schema: z.number().int(),
            },
        ],
        response: z.object({ download_link: z.string() }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/member/:member/merge-into',
        alias: 'mergeMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ member_id: z.string() }).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/members',
        alias: 'getMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(MemberResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'updateMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: MemberUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: MemberResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'removeMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'delete_related',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/members/:member/invite-placeholder',
        alias: 'invitePlaceholder',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/members/:member/make-placeholder',
        alias: 'makePlaceholder',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'updateProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectMemberUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'deleteProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects',
        alias: 'getProjects',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: z.number().int().gte(1).lte(2147483647).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z
            .object({
                data: z.array(ProjectResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects',
        alias: 'createProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'getProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'updateProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'deleteProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'getProjectMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(ProjectMemberResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'createProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ProjectMemberStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/reports',
        alias: 'getReports',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(ReportResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/reports',
        alias: 'createReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ReportStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'getReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'updateReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: ReportUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: DetailedReportResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/reports/:report',
        alias: 'deleteReport',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'report',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tags',
        alias: 'getTags',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tags',
        alias: 'createTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'updateTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string().min(1).max(255) }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'deleteTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tasks',
        alias: 'getTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'done',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z
            .object({
                data: z.array(TaskResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tasks',
        alias: 'createTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TaskStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'updateTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TaskUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'deleteTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'getTimeEntries',
        description: `If you only need time entries for a specific user, you can filter by &#x60;user_id&#x60;.
Users with the permission &#x60;time-entries:view:own&#x60; can only use this endpoint with their own user ID in the user_id filter.`,
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'offset',
                type: 'Query',
                schema: z.number().int().gte(0).lte(2147483647).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
        ],
        response: z
            .object({
                data: z.array(TimeEntryResource),
                meta: z.object({ total: z.number().int() }).passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'createTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryStoreRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'patch',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'updateMultipleTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryUpdateMultipleRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ success: z.string(), error: z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'deleteTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'ids',
                type: 'Query',
                schema: z.array(z.string().uuid()),
            },
        ],
        response: z.object({ success: z.string(), error: z.string() }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'updateTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: TimeEntryUpdateRequest,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'deleteTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/aggregate',
        alias: 'getAggregatedTimeEntries',
        description: `This endpoint allows you to filter time entries and aggregate them by different criteria.
The parameters &#x60;group&#x60; and &#x60;sub_group&#x60; allow you to group the time entries by different criteria.
If the group parameters are all set to &#x60;null&#x60; or are all missing, the endpoint will aggregate all filtered time entries.`,
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'group',
                type: 'Query',
                schema: z
                    .enum([
                        'day',
                        'week',
                        'month',
                        'year',
                        'user',
                        'project',
                        'task',
                        'client',
                        'billable',
                        'description',
                        'tag',
                    ])
                    .optional(),
            },
            {
                name: 'sub_group',
                type: 'Query',
                schema: z
                    .enum([
                        'day',
                        'week',
                        'month',
                        'year',
                        'user',
                        'project',
                        'task',
                        'client',
                        'billable',
                        'description',
                        'tag',
                    ])
                    .optional(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'fill_gaps_in_time_groups',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
        ],
        response: z
            .object({
                data: z
                    .object({
                        grouped_type: z.union([z.string(), z.null()]),
                        grouped_data: z.union([
                            z.array(
                                z
                                    .object({
                                        key: z.union([z.string(), z.null()]),
                                        seconds: z.number().int(),
                                        cost: z.union([z.number(), z.null()]),
                                        grouped_type: z.union([z.string(), z.null()]),
                                        grouped_data: z.union([
                                            z.array(
                                                z
                                                    .object({
                                                        key: z.union([z.string(), z.null()]),
                                                        seconds: z.number().int(),
                                                        cost: z.union([z.number(), z.null()]),
                                                        grouped_type: z.null(),
                                                        grouped_data: z.null(),
                                                    })
                                                    .passthrough()
                                            ),
                                            z.null(),
                                        ]),
                                    })
                                    .passthrough()
                            ),
                            z.null(),
                        ]),
                        seconds: z.number().int(),
                        cost: z.union([z.number(), z.null()]),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/aggregate/export',
        alias: 'exportAggregatedTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'format',
                type: 'Query',
                schema: z.enum(['csv', 'pdf', 'xlsx', 'ods']),
            },
            {
                name: 'group',
                type: 'Query',
                schema: z.enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ]),
            },
            {
                name: 'sub_group',
                type: 'Query',
                schema: z.enum([
                    'day',
                    'week',
                    'month',
                    'year',
                    'user',
                    'project',
                    'task',
                    'client',
                    'billable',
                    'description',
                    'tag',
                ]),
            },
            {
                name: 'history_group',
                type: 'Query',
                schema: z.enum(['day', 'week', 'month', 'year']),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: z.string().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: z.string(),
            },
            {
                name: 'end',
                type: 'Query',
                schema: z.string(),
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'fill_gaps_in_time_groups',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'debug',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
        ],
        response: z.union([
            z.object({ download_url: z.string() }).passthrough(),
            z.object({ html: z.string(), footer_html: z.string() }).passthrough(),
        ]),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/export',
        alias: 'exportTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'format',
                type: 'Query',
                schema: z.enum(['csv', 'pdf', 'xlsx', 'ods']),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: z.string(),
            },
            {
                name: 'end',
                type: 'Query',
                schema: z.string(),
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'debug',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'rounding_type',
                type: 'Query',
                schema: z.enum(['up', 'down', 'nearest']).optional(),
            },
            {
                name: 'rounding_minutes',
                type: 'Query',
                schema: rounding_minutes,
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string().uuid()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string().uuid()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string().uuid()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string().uuid()).min(1).optional(),
            },
        ],
        response: z.union([
            z.object({ download_url: z.string() }).passthrough(),
            z.object({ html: z.string(), footer_html: z.string() }).passthrough(),
        ]),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/public/reports',
        alias: 'getPublicReport',
        description: `This endpoint is public and does not require authentication. The report must be public and not expired.
The report is considered expired if the &#x60;public_until&#x60; field is set and the date is in the past.
The report is considered public if the &#x60;is_public&#x60; field is set to &#x60;true&#x60;.`,
        requestFormat: 'json',
        response: DetailedWithDataReportResource,
        errors: [
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me',
        alias: 'getMe',
        description: `This endpoint is independent of organization.`,
        requestFormat: 'json',
        response: z.object({ data: UserResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/api-tokens',
        alias: 'getApiTokens',
        description: `This endpoint is independent of organization.`,
        requestFormat: 'json',
        response: z.object({ data: ApiTokenCollection }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/users/me/api-tokens',
        alias: 'createApiToken',
        description: `The response will contain the access token that can be used to send authenticated API requests.
Please note that the access token is only shown in this response and cannot be retrieved later.`,
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string().min(1).max(255) }).passthrough(),
            },
        ],
        response: z.object({ data: ApiTokenWithAccessTokenResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({ message: z.string(), errors: z.record(z.array(z.string())) })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/users/me/api-tokens/:apiToken',
        alias: 'deleteApiToken',
        requestFormat: 'json',
        parameters: [
            {
                name: 'apiToken',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/users/me/api-tokens/:apiToken/revoke',
        alias: 'revokeApiToken',
        requestFormat: 'json',
        parameters: [
            {
                name: 'apiToken',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.void(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({ error: z.boolean(), key: z.string(), message: z.string() })
                    .passthrough(),
            },
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/memberships',
        alias: 'getMyMemberships',
        description: `This endpoint is independent of organization.`,
        requestFormat: 'json',
        response: z.object({ data: z.array(PersonalMembershipResource) }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/time-entries/active',
        alias: 'getMyActiveTimeEntry',
        description: `This endpoint is independent of organization.`,
        requestFormat: 'json',
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 401,
                description: `Unauthenticated`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
]);

export const api = new Zodios('/api', endpoints);

export function createApiClient(baseUrl: string, options?: ZodiosOptions) {
    return new Zodios(baseUrl, endpoints, options);
}
