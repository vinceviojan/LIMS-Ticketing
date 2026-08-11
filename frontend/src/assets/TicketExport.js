// assets/TicketExport.js — browser export helpers for Ticket Management
// Renders each ticket as a pixel-close reproduction of the official
// "LIMS Helpdesk Request Form" (Bureau of Soils and Water Management,
// Laboratory Services Division — Ref. Code BSWM_LS_FR_0140).
// deps: npm i pdf-lib
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib'

const PAGE = { width: 612, height: 792 } // US Letter
const MARGIN = 40
const CONTENT_W = PAGE.width - MARGIN * 2

// ── palette ──
const NAVY = rgb(0.09, 0.15, 0.35)      // section bar fill
const WHITE = rgb(1, 1, 1)
const BLACK = rgb(0.08, 0.08, 0.08)
const BORDER = rgb(0.15, 0.15, 0.15)
const LOGO_GREEN = rgb(0.2, 0.45, 0.2)

// ── row heights ──
const HEADER_H = 78          // top identity block
const BAR_H = 16             // navy section-title bars
const ROW_H = 16             // label/value row height
const DESC_H = 46             // description free-text box
const REMARKS_H = 34          // detailed-of-action free-text box
const FINAL_H = 34            // final remarks free-text box
const APPROVAL_H = 70         // closing-approval block

// Maps a UI ticket object (as produced by fetchTickets()) into the exact
// label set used on the printed form.
function mapTicketToFields(ticket) {
    return {
        ISSUE: ticket.category || '',
        TICKET_ID: ticket.ticket_no || `#${ticket.id}`,
        DATE_CREATED: ticket.created || '',
        NEW_STATUS: ticket.status || '',
        URGENCY_LEVEL: ticket.priority || '',
        TARGET_RESOLUTION: ticket.targetResolution || ticket.target_resolution || '',

        REQUESTED_BY: ticket.requester || '',
        OFFICE: ticket.office || ticket.affiliation || '',
        EMAIL: ticket.email || ticket.requesterEmail || '',
        FULL_NAME: ticket.requesterFullName || ticket.requester || '',
        PLEASE_SPECIFY: ticket.description || '',

        ASSIGNED_TO: ticket.assignedStaff || 'Unassigned',
        DATE_ACTION: ticket.dateAction || ticket.date_action || '',
        DATE_CLOSE: ticket.dateClose || ticket.approvalDate || '',
        RESOLUTION_REMARKS: ticket.remarks || '',

        FINAL_REMARKS: ticket.finalRemarks || ticket.final_remarks || '',

        TROUBLESHOOT_BY: ticket.assignedStaff || 'Unassigned',
        POSITION: ticket.assignedPosition || ticket.position || '',
        APPROVED_BY: ticket.approvedBy || '',
        APPROVED_POSITION: ticket.approverPosition || 'Project Development Officer III',
        DATE_CLOSED: ticket.dateClosed || '',
        CLOSED_DATE: ticket.closedDate || '',
    }
}

/** Word-wrap plain text to fit maxWidth for a given font/size. */
function wrapText(text, font, size, maxWidth) {
    const words = String(text || '').split(/\s+/).filter(Boolean)
    const lines = []
    let line = ''
    for (const word of words) {
        const test = line ? `${line} ${word}` : word
        if (font.widthOfTextAtSize(test, size) > maxWidth && line) {
            lines.push(line)
            line = word
        } else {
            line = test
        }
    }
    if (line) lines.push(line)
    return lines.length ? lines : ['']
}

/** Draw one ticket onto a single PDF page, styled like the printed form. */
async function drawTicketPage(pdfDoc, ticket, fonts) {
    const { regular, bold } = fonts
    const f = mapTicketToFields(ticket)
    const page = pdfDoc.addPage([PAGE.width, PAGE.height])

    // ── low-level helpers ──
    const strokeRect = (x, y, w, h, lw = 0.75) =>
        page.drawRectangle({ x, y, width: w, height: h, borderColor: BORDER, borderWidth: lw, color: undefined })
    const hLine = (x1, x2, y, lw = 0.75) =>
        page.drawLine({ start: { x: x1, y }, end: { x: x2, y }, thickness: lw, color: BORDER })
    const vLine = (x, y1, y2, lw = 0.75) =>
        page.drawLine({ start: { x, y: y1 }, end: { x, y: y2 }, thickness: lw, color: BORDER })
    const navyBar = (x, y, w, h) =>
        page.drawRectangle({ x, y, width: w, height: h, color: NAVY })
    const text = (t, x, y, size = 8, font = regular, color = BLACK) =>
        page.drawText(String(t || ''), { x, y, size, font, color })
    const centered = (t, xCenter, y, size = 9, font = bold, color = BLACK) => {
        const w = font.widthOfTextAtSize(String(t || ''), size)
        page.drawText(String(t || ''), { x: xCenter - w / 2, y, size, font, color })
    }

    let y = PAGE.height - MARGIN

    // ══════════════════════════════════════════════════════════
    // IDENTITY / HEADER BLOCK  (logo | agency name | ref-code table)
    // ══════════════════════════════════════════════════════════
    const headerTop = y
    const headerBottom = y - HEADER_H
    const logoColW = 78
    const titleColW = 288
    const refColW = CONTENT_W - logoColW - titleColW
    const logoX = MARGIN
    const titleX = logoX + logoColW
    const refX = titleX + titleColW

    strokeRect(MARGIN, headerBottom, CONTENT_W, HEADER_H)
    vLine(titleX, headerBottom, headerTop)
    vLine(refX, headerBottom, headerTop)

    // logo placeholder (simple circular emblem, no external asset needed)
    const logoCx = logoX + logoColW / 2
    const logoCy = headerBottom + HEADER_H / 2
    page.drawCircle({ x: logoCx, y: logoCy, size: 20, borderColor: LOGO_GREEN, borderWidth: 1.2 })
    page.drawCircle({ x: logoCx, y: logoCy, size: 13, borderColor: LOGO_GREEN, borderWidth: 0.8 })
    centered('DA', logoCx, logoCy - 3, 8, bold, LOGO_GREEN)

    // agency title, centered in its column
    const titleCx = titleX + titleColW / 2
    centered('BUREAU OF SOILS AND WATER MANAGEMENT', titleCx, headerTop - 20, 9.5, bold)
    centered('Laboratory Services Division', titleCx, headerTop - 32, 9, bold)
    centered('LIMS HELPDESK REQUEST FORM', titleCx, headerTop - 56, 10.5, bold)

    // reference-code mini table (4 stacked rows)
    const refRowH = HEADER_H / 4
    const refLabelW = refColW * 0.52
    for (let i = 1; i <= 3; i++) hLine(refX, refX + refColW, headerTop - refRowH * i)
    vLine(refX + refLabelW, headerBottom, headerTop)
    const refRows = [
        ['Reference Code:', 'BSWM_LS_FR_0140'],
        ['Effective date:', ticket.effectiveDate || 'September 12, 2025'],
        ['Rev. No.', String(ticket.revNo ?? '1')],
        ['Page No.:', '1 of 1'],
    ]
    refRows.forEach(([lbl, val], i) => {
        const rowY = headerTop - refRowH * i - refRowH / 2 - 3
        text(lbl, refX + 6, rowY, 7.5, bold)
        text(val, refX + refLabelW + 6, rowY, 7, regular)
    })

    y = headerBottom - 8

    // ══════════════════════════════════════════════════════════
    // TOP META TABLE — ISSUE / TICKET NO / STATUS / URGENCY / TARGET
    // ══════════════════════════════════════════════════════════
    const metaTop = y
    const rowIssue = ROW_H
    const rowTicket = ROW_H
    const rowUrgency = ROW_H
    const metaH = rowIssue + rowTicket + rowUrgency
    const metaBottom = metaTop - metaH
    const midX = MARGIN + CONTENT_W / 2

    strokeRect(MARGIN, metaBottom, CONTENT_W, metaH)
    hLine(MARGIN, MARGIN + CONTENT_W, metaTop - rowIssue,)
    hLine(MARGIN, MARGIN + CONTENT_W, metaTop - rowIssue - rowTicket)
    vLine(midX, metaTop - rowIssue, metaTop) // only on ticket/status row not issue row (issue spans full width)
    vLine(midX, metaBottom, metaTop - rowIssue - rowTicket)

    // row 1: ISSUE (full width)
    text('ISSUE:', MARGIN + 6, metaTop - 11, 8, bold)
    text(f.ISSUE, MARGIN + 60, metaTop - 11, 9, regular)

    // row 2: TICKET NO | STATUS/DATE
    const r2y = metaTop - rowIssue - 11
    text('TICKET NO:', MARGIN + 6, r2y, 8, bold)
    text(f.TICKET_ID, MARGIN + 68, r2y, 9, regular)
    text('DATE SUBMITTED:', midX + 6, r2y, 8, bold)
    text(f.DATE_CREATED, midX + 100, r2y, 9, regular)

    // row 3: TICKET STATUS | URGENCY LEVEL
    const r3y = metaTop - rowIssue - rowTicket - 11
    text('TICKET STATUS:', MARGIN + 6, r3y, 8, bold)
    text(f.NEW_STATUS, MARGIN + 90, r3y, 9, regular)
    text('URGENCY LEVEL:', midX + 6, r3y, 8, bold)
    text(f.URGENCY_LEVEL, midX + 95, r3y, 9, regular)

    y = metaBottom - 10

    // ══════════════════════════════════════════════════════════
    // END USER DETAILS
    // ══════════════════════════════════════════════════════════
    const euTop = y
    navyBar(MARGIN, euTop - BAR_H, CONTENT_W, BAR_H)
    text('END USER DETAILS', MARGIN + 6, euTop - BAR_H + 4.5, 9, bold, WHITE)

    const euRows = [
        ['REQUESTED BY:', f.REQUESTED_BY],
        ['AFFILIATION/OFFICE:', f.OFFICE],
        ['EMAIL ADDRESS:', f.EMAIL],
        ['FULL NAME:', f.FULL_NAME],
    ]
    const euRowsTop = euTop - BAR_H
    const euRowsH = ROW_H * euRows.length
    strokeRect(MARGIN, euRowsTop - euRowsH, CONTENT_W, euRowsH)
    euRows.forEach((_, i) => {
        if (i > 0) hLine(MARGIN, MARGIN + CONTENT_W, euRowsTop - ROW_H * i)
    })
    euRows.forEach(([lbl, val], i) => {
        const rowY = euRowsTop - ROW_H * i - 11
        text(lbl, MARGIN + 6, rowY, 8, bold)
        text(val, MARGIN + 150, rowY, 9, regular)
    })

    // description block, attached under end-user rows
    const descTop = euRowsTop - euRowsH
    navyBar(MARGIN, descTop - BAR_H, CONTENT_W, BAR_H)
    text('DESCRIPTION / DETAILS OF THE REQUIREMENT', MARGIN + 6, descTop - BAR_H + 4.5, 8, bold, WHITE)
    const descBoxTop = descTop - BAR_H
    strokeRect(MARGIN, descBoxTop - DESC_H, CONTENT_W, DESC_H)
    let dy = descBoxTop - 12
    for (const line of wrapText(f.PLEASE_SPECIFY, regular, 9, CONTENT_W - 12)) {
        text(line, MARGIN + 6, dy, 9)
        dy -= 12
        if (dy < descBoxTop - DESC_H + 6) break
    }

    y = descBoxTop - DESC_H - 10

    // ══════════════════════════════════════════════════════════
    // RESOLUTION
    // ══════════════════════════════════════════════════════════
    const resTop = y
    navyBar(MARGIN, resTop - BAR_H, CONTENT_W, BAR_H)
    text('RESOLUTION', MARGIN + 6, resTop - BAR_H + 4.5, 9, bold, WHITE)

    const resRowTop = resTop - BAR_H
    strokeRect(MARGIN, resRowTop - ROW_H, CONTENT_W, ROW_H)
    vLine(midX, resRowTop - ROW_H, resRowTop)
    text('RESPONSIBLE PERSON:', MARGIN + 6, resRowTop - 11, 8, bold)
    text(f.ASSIGNED_TO, MARGIN + 130, resRowTop - 11, 9, regular)
    text('DATE OF ACTION:', midX + 6, resRowTop - 11, 8, bold)
    text(f.DATE_ACTION, midX + 100, resRowTop - 11, 9, regular)

    const detailTop = resRowTop - ROW_H
    navyBar(MARGIN, detailTop - BAR_H, CONTENT_W, BAR_H)
    text('DETAILED OF ACTION', MARGIN + 6, detailTop - BAR_H + 4.5, 8, bold, WHITE)
    const detailBoxTop = detailTop - BAR_H
    strokeRect(MARGIN, detailBoxTop - REMARKS_H, CONTENT_W, REMARKS_H)
    let ry = detailBoxTop - 12
    for (const line of wrapText(f.RESOLUTION_REMARKS, regular, 9, CONTENT_W - 12)) {
        text(line, MARGIN + 6, ry, 9)
        ry -= 12
        if (ry < detailBoxTop - REMARKS_H + 6) break
    }

    y = detailBoxTop - REMARKS_H - 10

    // ══════════════════════════════════════════════════════════
    // FINAL REMARKS
    // ══════════════════════════════════════════════════════════
    const frTop = y
    navyBar(MARGIN, frTop - BAR_H, CONTENT_W, BAR_H)
    centered('FINAL REMARKS', MARGIN + CONTENT_W / 2, frTop - BAR_H + 4.5, 9, bold, WHITE)
    const frBoxTop = frTop - BAR_H
    strokeRect(MARGIN, frBoxTop - FINAL_H, CONTENT_W, FINAL_H)
    let fy = frBoxTop - 12
    for (const line of wrapText(f.FINAL_REMARKS, regular, 9, CONTENT_W - 12)) {
        text(line, MARGIN + 6, fy, 9)
        fy -= 12
        if (fy < frBoxTop - FINAL_H + 6) break
    }

    y = frBoxTop - FINAL_H - 10

    // ══════════════════════════════════════════════════════════
    // SERVICE REQUEST TICKET CLOSING APPROVAL
    // ══════════════════════════════════════════════════════════
    const apTop = y
    navyBar(MARGIN, apTop - BAR_H, CONTENT_W, BAR_H)
    centered('SERVICE REQUEST TICKET CLOSING APPROVAL', MARGIN + CONTENT_W / 2, apTop - BAR_H + 4.5, 9, bold, WHITE)

    const apBoxTop = apTop - BAR_H
    strokeRect(MARGIN, apBoxTop - APPROVAL_H, CONTENT_W, APPROVAL_H)
    vLine(midX, apBoxTop - APPROVAL_H, apBoxTop)
    hLine(MARGIN, MARGIN + CONTENT_W, apBoxTop - APPROVAL_H + ROW_H) // approval-date row divider

    text('TROUBLESHOOT BY:', MARGIN + 6, apBoxTop - 11, 8, bold)
    text('APPROVED BY:', midX + 6, apBoxTop - 11, 8, bold)

    text(f.TROUBLESHOOT_BY, MARGIN + 6, apBoxTop - 32, 9, regular)
    text(f.POSITION, MARGIN + 6, apBoxTop - 44, 8, regular)

    text(f.APPROVED_BY, midX + 6, apBoxTop - 32, 9, regular)
    text(f.APPROVED_POSITION, midX + 6, apBoxTop - 44, 8, regular)

    const apBottomRowY = apBoxTop - APPROVAL_H + 11
    text(`APPROVAL DATE: ${f.DATE_CLOSED}`, MARGIN + 6, apBottomRowY, 7.5, bold)
    text(`CLOSED DATE: ${f.CLOSED_DATE}`, midX + 6, apBottomRowY, 7.5, bold)

    return page
}

/** Render one ticket into a single-page PDF and return the bytes. */
export async function renderTicketPdf(ticket) {
    const pdfDoc = await PDFDocument.create()
    const regular = await pdfDoc.embedFont(StandardFonts.Helvetica)
    const bold = await pdfDoc.embedFont(StandardFonts.HelveticaBold)
    await drawTicketPage(pdfDoc, ticket, { regular, bold })
    return pdfDoc.save()
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

/** Export a single ticket as a one-page PDF. */
export async function exportTicketToPdf(ticket) {
    const bytes = await renderTicketPdf(ticket)
    downloadBlob(new Blob([bytes], { type: 'application/pdf' }), `${ticket.ticket_no || ticket.id}.pdf`)
}

/** Export many tickets as a single multi-page PDF (one page per ticket). */
export async function exportTicketsToPdf(tickets, filename = 'tickets-export.pdf') {
    const pdfDoc = await PDFDocument.create()
    const regular = await pdfDoc.embedFont(StandardFonts.Helvetica)
    const bold = await pdfDoc.embedFont(StandardFonts.HelveticaBold)
    for (const ticket of tickets) {
        await drawTicketPage(pdfDoc, ticket, { regular, bold })
    }
    const bytes = await pdfDoc.save()
    downloadBlob(new Blob([bytes], { type: 'application/pdf' }), filename)
}

/** Export tickets as CSV. */
export function exportTicketsToCSV(tickets, filename = 'tickets-export.csv') {
    const keys = ['TICKET_ID', 'ISSUE', 'DATE_CREATED', 'NEW_STATUS', 'URGENCY_LEVEL', 'TARGET_RESOLUTION',
        'REQUESTED_BY', 'OFFICE', 'EMAIL', 'FULL_NAME', 'PLEASE_SPECIFY',
        'ASSIGNED_TO', 'DATE_ACTION', 'DATE_CLOSE', 'RESOLUTION_REMARKS', 'FINAL_REMARKS',
        'APPROVED_BY', 'DATE_CLOSED', 'CLOSED_DATE']
    const cell = (v) => (/[",\n]/.test(v = String(v ?? '')) ? `"${v.replace(/"/g, '""')}"` : v)
    const rows = tickets.map((t) => {
        const f = mapTicketToFields(t)
        return keys.map((k) => cell(f[k])).join(',')
    })
    const csv = [keys.join(','), ...rows].join('\n')
    downloadBlob(new Blob([csv], { type: 'text/csv;charset=utf-8;' }), filename)
}

/** Export tickets as JSON. */
export function exportTicketsToJSON(tickets, filename = 'tickets-export.json') {
    const data = tickets.map(mapTicketToFields)
    downloadBlob(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }), filename)
}