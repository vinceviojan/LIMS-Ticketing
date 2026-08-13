// assets/TicketExport.js
// Ticket export helpers for the LIMS Helpdesk Request Form.
//
// Uses TicketForm.html as the single source of truth.
// The exported ticket therefore uses the exact same HTML/CSS
// layout and color design as the on-screen ticket form.
//
// Supported exports:
//   - Single ticket -> PDF
//   - Multiple tickets -> PDF
//   - Single ticket -> HTML
//   - Multiple tickets -> HTML
//   - Multiple tickets -> CSV
//   - Multiple tickets -> JSON

const TEMPLATE_URL = new URL('./TicketForm.html', import.meta.url)

// ================================================================
// FIELD MAPPING
// ================================================================

const FIELD_KEYS = [
    'ISSUE',
    'TICKET_ID',
    'DATE_CREATED',
    'NEW_STATUS',
    'URGENCY_LEVEL',
    'TARGET_RESOLUTION',

    'REQUESTED_BY',
    'OFFICE',
    'EMAIL',
    'FULL_NAME',

    'PLEASE_SPECIFY',

    'ASSIGNED_TO',
    'DATE_ACTION',
    'DATE_CLOSE',

    'RESOLUTION_REMARKS',
    'FINAL_REMARKS',

    'POSITION',

    'APPROVED_BY',
    'APPROVED_POSITION',

    'DATE_CLOSED',
    'CLOSED_DATE',

    'REF_CODE',
    'EFFECTIVE_DATE',
    'REV_NO',
    'PAGE_NO',
]

// ================================================================
// MAP TICKET DATA TO FORM DATA
// ================================================================

export function mapTicketToFields(ticket = {}) {
    const fields = {
        // ----------------------------------------------------------
        // TICKET INFORMATION
        // ----------------------------------------------------------

        ISSUE:
            ticket.category ??
            ticket.issue ??
            '',

        TICKET_ID:
            ticket.ticket_no ??
            ticket.ticketNo ??
            (ticket.id != null ? `#${ticket.id}` : ''),

        DATE_CREATED:
            ticket.created ??
            ticket.created_at ??
            ticket.dateCreated ??
            '',

        NEW_STATUS:
            ticket.status ??
            '',

        URGENCY_LEVEL:
            ticket.priority ??
            ticket.urgency_level ??
            ticket.urgencyLevel ??
            '',

        TARGET_RESOLUTION:
            ticket.targetResolution ??
            ticket.target_resolution_date ??
            ticket.target_resolution ??
            '',

        // ----------------------------------------------------------
        // END USER DETAILS
        // ----------------------------------------------------------

        REQUESTED_BY:
            ticket.requester ??
            ticket.requestedBy ??
            '',

        OFFICE:
            ticket.office ??
            ticket.affiliation ??
            '',

        EMAIL:
            ticket.email ??
            ticket.requesterEmail ??
            '',

        FULL_NAME:
            ticket.requesterFullName ??
            ticket.fullName ??
            ticket.requester ??
            '',

        // ----------------------------------------------------------
        // DESCRIPTION
        // ----------------------------------------------------------

        PLEASE_SPECIFY:
            ticket.description ??
            ticket.details ??
            ticket.pleaseSpecify ??
            '',

        // ----------------------------------------------------------
        // RESOLUTION & TROUBLESHOOTER
        // ----------------------------------------------------------

        ASSIGNED_TO:
            (typeof ticket.assignedStaff === 'string' && ticket.assignedStaff.trim() ? ticket.assignedStaff : null) ||
            ticket.assigned_staff?.name ||
            (ticket.assigned_staff && typeof ticket.assigned_staff === 'object' ? `${ticket.assigned_staff.first_name || ''} ${ticket.assigned_staff.last_name || ''}`.trim() : null) ||
            (typeof ticket.assigned_staff === 'string' ? ticket.assigned_staff : null) ||
            ticket.assigned_to ||
            ticket.assignedTo ||
            '',

        POSITION:
            ticket.assignedPosition ||
            ticket.assigned_position ||
            (ticket.assigned_staff && typeof ticket.assigned_staff === 'object' ? ticket.assigned_staff.position : '') ||
            (ticket.assignedStaff && typeof ticket.assignedStaff === 'object' ? ticket.assignedStaff.position : '') ||
            (ticket.raw_assigned_staff && typeof ticket.raw_assigned_staff === 'object' ? ticket.raw_assigned_staff.position : '') ||
            ticket.position ||
            'IT Staff',

        DATE_ACTION:
            ticket.dateAction ??
            ticket.date_action ??
            '',

        DATE_CLOSE:
            ticket.dateClose ??
            ticket.date_close ??
            ticket.date_closed ??
            ticket.approvalDate ??
            '',

        RESOLUTION_REMARKS:
            ticket.remarks ??
            ticket.resolutionRemarks ??
            ticket.resolution_remarks ??
            '',

        FINAL_REMARKS:
            ticket.finalRemarks ??
            ticket.final_remarks ??
            '',

        // ----------------------------------------------------------
        // APPROVAL
        // ----------------------------------------------------------

        APPROVED_BY:
            (typeof ticket.approvedBy === 'string' && ticket.approvedBy.trim() ? ticket.approvedBy : null) ||
            ticket.approved_by?.name ||
            (ticket.approved_by && typeof ticket.approved_by === 'object' ? `${ticket.approved_by.first_name || ''} ${ticket.approved_by.last_name || ''}`.trim() : null) ||
            (typeof ticket.approved_by === 'string' ? ticket.approved_by : null) ||
            'Authorized Approver',

        APPROVED_POSITION:
            ticket.approvedPosition ||
            ticket.approved_position ||
            (ticket.approved_by && typeof ticket.approved_by === 'object' ? ticket.approved_by.position : '') ||
            (ticket.approvedBy && typeof ticket.approvedBy === 'object' ? ticket.approvedBy.position : '') ||
            (ticket.raw_approved_by && typeof ticket.raw_approved_by === 'object' ? ticket.raw_approved_by.position : '') ||
            'Laboratory Chief',

        // ----------------------------------------------------------
        // CLOSING
        // ----------------------------------------------------------

        DATE_CLOSED:
            ticket.dateClosed ??
            ticket.date_closed ??
            '',

        CLOSED_DATE:
            ticket.closedDate ??
            ticket.closed_date ??
            ticket.date_closed ??
            '',

        // ----------------------------------------------------------
        // FORM HEADER
        // ----------------------------------------------------------

        REF_CODE:
            ticket.refCode ??
            'BSWM_LS_FR_0140',

        EFFECTIVE_DATE:
            ticket.effectiveDate ??
            'September 12, 2025',

        REV_NO:
            ticket.revNo != null
                ? String(ticket.revNo)
                : '1',

        PAGE_NO:
            ticket.pageNo ??
            '1 of 1',
    }

    console.log('📌 [TicketExport API Data Mapped]:', {
        rawInput: ticket,
        mappedFields: fields,
        assignedStaffName: fields.ASSIGNED_TO,
        assignedStaffPosition: fields.POSITION,
        approvedByName: fields.APPROVED_BY,
        approvedByPosition: fields.APPROVED_POSITION,
    })

    return fields
}

// ================================================================
// LOAD TICKET FORM
// ================================================================

let templateDocPromise = null

async function loadTemplateDoc() {
    if (!templateDocPromise) {
        templateDocPromise = fetch(TEMPLATE_URL)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `TicketExport: Failed to load TicketForm.html (${response.status})`
                    )
                }

                return response.text()
            })
            .then((html) => {
                return new DOMParser()
                    .parseFromString(html, 'text/html')
            })
    }

    return templateDocPromise
}

// ================================================================
// EXTRACT CSS
// ================================================================
//
// Extracts ALL CSS from TicketForm.html.
//
// This means the exported ticket automatically receives:
// - Navy section colors
// - Light-blue label colors
// - Value-cell colors
// - Approval colors
// - Borders
// - Typography
// - Print styles
// - Any future design changes made in TicketForm.html
//
// ================================================================

function extractStyles(templateDoc) {
    return Array
        .from(
            templateDoc.querySelectorAll('style')
        )
        .map((style) => style.outerHTML)
        .join('\n')
}

// ================================================================
// CLONE FORM
// ================================================================

function cloneFormNode(templateDoc) {
    const template =
        templateDoc.getElementById(
            'lims-ticket-template'
        )

    if (!template) {
        throw new Error(
            'TicketExport: #lims-ticket-template not found in TicketForm.html'
        )
    }

    if (!template.content.firstElementChild) {
        throw new Error(
            'TicketExport: Ticket template is empty.'
        )
    }

    return template
        .content
        .firstElementChild
        .cloneNode(true)
}

// ================================================================
// FILL FORM FIELDS
// ================================================================

function fillFields(root, fields) {
    FIELD_KEYS.forEach((key) => {
        if (!(key in fields)) {
            return
        }

        root
            .querySelectorAll(
                `[data-field="${key}"]`
            )
            .forEach((element) => {
                element.textContent =
                    fields[key] == null
                        ? ''
                        : String(fields[key])
            })
    })
}

// ================================================================
// SET LOGO
// ================================================================
//
// The current TicketForm.html contains ONE logo.
// ================================================================

function setLogoSrc(root, src) {
    const image =
        root.querySelector(
            '[data-role="logo-img-1"]'
        )

    if (!image) {
        return
    }

    if (src) {
        image.src = src
        image.style.display = ''
    } else {
        image.style.display = 'none'
    }
}

// ================================================================
// BUILD FILLED FORM
// ================================================================

async function buildFilledFormNode(
    ticket,
    logos = {}
) {
    const templateDoc =
        await loadTemplateDoc()

    const node =
        cloneFormNode(
            templateDoc
        )

    const fields =
        mapTicketToFields(
            ticket
        )

    fillFields(
        node,
        fields
    )

    // ------------------------------------------------------------
    // Logo priority:
    //
    // 1. logos.logo1
    // 2. ticket.logoUrl
    // 3. TicketForm.html default BSWM_LOGO.png
    // ------------------------------------------------------------

    const logo =
        logos.logo1 ||
        ticket.logoUrl ||
        new URL(
            './BSWM_LOGO.png',
            TEMPLATE_URL
        ).href

    setLogoSrc(
        node,
        logo
    )

    return node
}

// ================================================================
// BUILD PRINT DOCUMENT
// ================================================================

async function buildPrintDocument(
    formNodes
) {
    const templateDoc =
        await loadTemplateDoc()

    // Get the exact CSS from TicketForm.html.
    const styles =
        extractStyles(
            templateDoc
        )

    const body =
        formNodes
            .map(
                (node) => node.outerHTML
            )
            .join('\n')

    return `
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        LIMS Helpdesk Ticket Export
    </title>

    ${styles}

</head>

<body>

    ${body}

</body>

</html>
`
}

// ================================================================
// PRINT HTML DOCUMENT
// ================================================================

function printHtmlDocument(
    html
) {
    return new Promise(
        (resolve, reject) => {
            const iframe =
                document.createElement(
                    'iframe'
                )

            iframe.style.position =
                'fixed'

            iframe.style.right =
                '0'

            iframe.style.bottom =
                '0'

            iframe.style.width =
                '0'

            iframe.style.height =
                '0'

            iframe.style.border =
                '0'

            iframe.style.visibility =
                'hidden'

            document.body.appendChild(
                iframe
            )

            const cleanup = () => {
                setTimeout(
                    () => {
                        iframe.remove()
                    },
                    1000
                )
            }

            iframe.onload = () => {
                try {
                    const win =
                        iframe.contentWindow

                    const documentInsideIframe =
                        iframe.contentDocument

                    if (
                        !win ||
                        !documentInsideIframe
                    ) {
                        throw new Error(
                            'TicketExport: Unable to access print document.'
                        )
                    }

                    const images =
                        Array.from(
                            documentInsideIframe.images
                        )

                    const waitForImages =
                        Promise.all(
                            images.map(
                                (image) => {
                                    if (
                                        image.complete
                                    ) {
                                        return Promise.resolve()
                                    }

                                    return new Promise(
                                        (resolveImage) => {
                                            image.addEventListener(
                                                'load',
                                                resolveImage,
                                                {
                                                    once: true
                                                }
                                            )

                                            image.addEventListener(
                                                'error',
                                                resolveImage,
                                                {
                                                    once: true
                                                }
                                            )
                                        }
                                    )
                                }
                            )
                        )

                    waitForImages.then(
                        () => {
                            win.focus()

                            win.print()

                            cleanup()

                            resolve()
                        }
                    )
                } catch (error) {
                    cleanup()

                    reject(error)
                }
            }

            iframe.onerror = () => {
                cleanup()

                reject(
                    new Error(
                        'TicketExport: Failed to load print document.'
                    )
                )
            }

            iframe.srcdoc =
                html
        }
    )
}

// ================================================================
// DOWNLOAD BLOB
// ================================================================

function downloadBlob(
    blob,
    filename
) {
    const url =
        URL.createObjectURL(
            blob
        )

    const anchor =
        document.createElement(
            'a'
        )

    anchor.href =
        url

    anchor.download =
        filename

    document.body.appendChild(
        anchor
    )

    anchor.click()

    anchor.remove()

    setTimeout(
        () => {
            URL.revokeObjectURL(
                url
            )
        },
        100
    )
}

// ================================================================
// EXPORT SINGLE TICKET TO PDF
// ================================================================

export async function exportTicketToPdf(
    ticket,
    logos
) {
    console.log('🚀 [Export Single Ticket PDF Initiated]:', ticket)
    if (!ticket) {
        throw new Error(
            'No ticket provided for export.'
        )
    }

    const node =
        await buildFilledFormNode(
            ticket,
            logos
        )

    const html =
        await buildPrintDocument(
            [node]
        )

    await printHtmlDocument(
        html
    )
}

// ================================================================
// EXPORT MULTIPLE TICKETS TO PDF
// ================================================================
//
// One ticket = one page.
//
// Example:
// 1 of 5
// 2 of 5
// 3 of 5
// 4 of 5
// 5 of 5
//
// ================================================================

export async function exportTicketsToPdf(
    tickets = [],
    getLogos
) {
    console.log('🚀 [Export Multiple Tickets PDF Initiated]:', tickets)
    if (!tickets.length) {
        throw new Error(
            'No tickets selected for export.'
        )
    }

    const total =
        tickets.length

    const nodes = []

    for (
        let index = 0;
        index < total;
        index++
    ) {
        const ticket =
            tickets[index]

        const logos =
            typeof getLogos === 'function'
                ? getLogos(ticket)
                : undefined

        const node =
            await buildFilledFormNode(
                ticket,
                logos
            )

        // --------------------------------------------------------
        // Automatically update page number.
        // --------------------------------------------------------

        node
            .querySelectorAll(
                '[data-field="PAGE_NO"]'
            )
            .forEach(
                (element) => {
                    element.textContent =
                        `${index + 1} of ${total}`
                }
            )

        nodes.push(
            node
        )
    }

    const html =
        await buildPrintDocument(
            nodes
        )

    await printHtmlDocument(
        html
    )
}

// ================================================================
// EXPORT SINGLE TICKET TO HTML
// ================================================================

export async function exportTicketToHtml(
    ticket,
    filename,
    logos
) {
    if (!ticket) {
        throw new Error(
            'No ticket provided for export.'
        )
    }

    const node =
        await buildFilledFormNode(
            ticket,
            logos
        )

    const html =
        await buildPrintDocument(
            [node]
        )

    const name =
        filename ||
        `${ticket.ticket_no || ticket.id || 'ticket'}.html`

    downloadBlob(
        new Blob(
            [html],
            {
                type:
                    'text/html;charset=utf-8;'
            }
        ),
        name
    )
}

// ================================================================
// EXPORT MULTIPLE TICKETS TO HTML
// ================================================================

export async function exportTicketsToHtml(
    tickets = [],
    filename = 'tickets-export.html',
    getLogos
) {
    if (!tickets.length) {
        throw new Error(
            'No tickets selected for export.'
        )
    }

    const nodes = []

    for (
        const ticket of tickets
    ) {
        const logos =
            typeof getLogos === 'function'
                ? getLogos(ticket)
                : undefined

        const node =
            await buildFilledFormNode(
                ticket,
                logos
            )

        nodes.push(
            node
        )
    }

    const html =
        await buildPrintDocument(
            nodes
        )

    downloadBlob(
        new Blob(
            [html],
            {
                type:
                    'text/html;charset=utf-8;'
            }
        ),
        filename
    )
}

// ================================================================
// CSV HELPER
// ================================================================

function csvCell(
    value
) {
    const text =
        String(
            value ?? ''
        )

    if (
        /[",\n\r]/.test(
            text
        )
    ) {
        return `"${text.replace(
            /"/g,
            '""'
        )}"`
    }

    return text
}

// ================================================================
// EXPORT TICKETS TO CSV
// ================================================================

export function exportTicketsToCSV(
    tickets = [],
    filename = 'tickets-export.csv'
) {
    if (!tickets.length) {
        throw new Error(
            'No tickets selected for export.'
        )
    }

    const keys = [
        'TICKET_ID',
        'ISSUE',
        'DATE_CREATED',
        'NEW_STATUS',
        'URGENCY_LEVEL',
        'TARGET_RESOLUTION',

        'REQUESTED_BY',
        'OFFICE',
        'EMAIL',
        'FULL_NAME',

        'PLEASE_SPECIFY',

        'ASSIGNED_TO',
        'DATE_ACTION',
        'DATE_CLOSE',

        'RESOLUTION_REMARKS',
        'FINAL_REMARKS',

        'POSITION',

        'APPROVED_BY',
        'APPROVED_POSITION',

        'DATE_CLOSED',
        'CLOSED_DATE',
    ]

    const header =
        keys.join(',')

    const rows =
        tickets.map(
            (ticket) => {
                const fields =
                    mapTicketToFields(
                        ticket
                    )

                return keys
                    .map(
                        (key) =>
                            csvCell(
                                fields[key]
                            )
                    )
                    .join(',')
            }
        )

    const csv =
        [
            header,
            ...rows
        ].join('\n')

    downloadBlob(
        new Blob(
            [csv],
            {
                type:
                    'text/csv;charset=utf-8;'
            }
        ),
        filename
    )
}

// ================================================================
// EXPORT TICKETS TO JSON
// ================================================================

export function exportTicketsToJSON(
    tickets = [],
    filename = 'tickets-export.json'
) {
    if (!tickets.length) {
        throw new Error(
            'No tickets selected for export.'
        )
    }

    const data =
        tickets.map(
            mapTicketToFields
        )

    downloadBlob(
        new Blob(
            [
                JSON.stringify(
                    data,
                    null,
                    2
                )
            ],
            {
                type:
                    'application/json'
            }
        ),
        filename
    )
}

// ================================================================
// DEFAULT EXPORT
// ================================================================

export default {
    mapTicketToFields,

    exportTicketToPdf,
    exportTicketsToPdf,

    exportTicketToHtml,
    exportTicketsToHtml,

    exportTicketsToCSV,
    exportTicketsToJSON,
}