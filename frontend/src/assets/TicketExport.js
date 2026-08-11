// assets/TicketExport.js — browser export helpers for Ticket Management
// Renders each ticket as a pixel-close reproduction of the official
// "LIMS Helpdesk Request Form" (Bureau of Soils and Water Management,
// Laboratory Services Division — Ref. Code BSWM_LS_FR_0140).
//
// Refactor notes (vs. previous version):
//  - Meta table now has the correct 4-row / navy-label-cell structure and
//    actually renders TARGET_RESOLUTION (was mapped but never drawn).
//  - Resolution block now has its own RESPONSIBLE PERSON row plus a
//    DATE OF ACTION / DATE CLOSE row (DATE_CLOSE was mapped but never drawn).
//  - Header now embeds the real DA / BSWM seal artwork instead of a drawn
//    placeholder circle, and the title/ref-code column dividers match the
//    source form (title splits 2/3 - 1/3, ref-code row 3 splits into 4 cells).
//  - Description box gets its narrow attachment-icon column back.
// deps: npm i pdf-lib
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib'

const PAGE = { width: 612, height: 792 } // US Letter
const MARGIN = 40
const CONTENT_W = PAGE.width - MARGIN * 2

// ── palette (matches the source Word template's theme fill 002060) ──
const NAVY = rgb(0x00 / 255, 0x20 / 255, 0x60 / 255)
const WHITE = rgb(1, 1, 1)
const BLACK = rgb(0.08, 0.08, 0.08)
const BORDER = rgb(0.15, 0.15, 0.15)

// ── row heights ──
const HEADER_H = 78          // top identity block
const BAR_H = 16             // navy section-title bars
const ROW_H = 16             // label/value row height
const DESC_H = 46            // description free-text box
const REMARKS_H = 34         // detailed-of-action free-text box
const FINAL_H = 34           // final remarks free-text box
const APPROVAL_H = 70        // closing-approval block
const ICON_COL_W = 18        // narrow attachment-icon column width

// Base64 PNG of the official DA / BSWM seals, extracted from the source
// Word template (ticket-template.docx -> word/media/image1.png), so the
// exported PDF uses the real letterhead artwork instead of a placeholder.
const LOGO_PNG_BASE64 =
    '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'

function base64ToUint8Array(base64) {
    if (typeof atob === 'function') {
        const binary = atob(base64)
        const bytes = new Uint8Array(binary.length)
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i)
        return bytes
    }
    // Node / SSR fallback
    return Uint8Array.from(Buffer.from(base64, 'base64'))
}

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
async function drawTicketPage(pdfDoc, ticket, fonts, logoImage) {
    const { regular, bold } = fonts
    const f = mapTicketToFields(ticket)
    const page = pdfDoc.addPage([PAGE.width, PAGE.height])

    // ── low-level helpers ──
    const strokeRect = (x, y, w, h, lw = 0.75) =>
        page.drawRectangle({ x, y, width: w, height: h, borderColor: BORDER, borderWidth: lw, color: undefined })
    const fillRect = (x, y, w, h, color) =>
        page.drawRectangle({ x, y, width: w, height: h, color })
    const hLine = (x1, x2, y, lw = 0.75) =>
        page.drawLine({ start: { x: x1, y }, end: { x: x2, y }, thickness: lw, color: BORDER })
    const vLine = (x, y1, y2, lw = 0.75) =>
        page.drawLine({ start: { x, y: y1 }, end: { x, y: y2 }, thickness: lw, color: BORDER })
    const navyBar = (x, y, w, h) => fillRect(x, y, w, h, NAVY)
    const text = (t, x, y, size = 8, font = regular, color = BLACK) =>
        page.drawText(String(t || ''), { x, y, size, font, color })
    const centered = (t, xCenter, y, size = 9, font = bold, color = BLACK) => {
        const w = font.widthOfTextAtSize(String(t || ''), size)
        page.drawText(String(t || ''), { x: xCenter - w / 2, y, size, font, color })
    }
    // A label cell with a navy fill + white bold text, used in the meta table.
    const navyLabel = (t, x, y, w, h, size = 8) => {
        fillRect(x, y, w, h, NAVY)
        text(t, x + 6, y + h / 2 - 3, size, bold, WHITE)
    }
    // Small attachment/paperclip glyph built from straight segments only
    // (pdf-lib's SVG path support doesn't handle arc commands, so this is
    // drawn natively instead of via drawSvgPath).
    const drawPaperclipIcon = (cx, cy, s = 1, color = WHITE) => {
        const pts = [
            [6, 0], [2, 0], [2, 10], [8, 10], [8, 2], [4, 2], [4, 8],
        ].map(([px, py]) => ({ x: cx + px * s, y: cy + py * s }))
        for (let i = 0; i < pts.length - 1; i++) {
            page.drawLine({ start: pts[i], end: pts[i + 1], thickness: 1 * s, color })
        }
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

    // logo: real DA / BSWM seal artwork, embedded from the source template
    if (logoImage) {
        const maxW = logoColW - 16
        const maxH = HEADER_H - 16
        const scale = Math.min(maxW / logoImage.width, maxH / logoImage.height)
        const w = logoImage.width * scale
        const h = logoImage.height * scale
        page.drawImage(logoImage, {
            x: logoX + (logoColW - w) / 2,
            y: headerBottom + (HEADER_H - h) / 2,
            width: w,
            height: h,
        })
    }

    // ref-code table: 3 rows — Reference Code / Effective date / (Rev.No + Page No.)
    const refRowH = HEADER_H / 3
    const refLabelW = refColW * 0.42
    hLine(refX, refX + refColW, headerTop - refRowH)
    hLine(refX, refX + refColW, headerTop - refRowH * 2)
    vLine(refX + refLabelW, headerBottom, headerTop)

    const rcY1 = headerTop - refRowH / 2 - 3
    text('Reference Code:', refX + 6, rcY1, 7.5, bold)
    text('BSWM_LS_FR_0140', refX + refLabelW + 6, rcY1, 8, bold)

    const rcY2 = headerTop - refRowH - refRowH / 2 - 3
    text('Effective date:', refX + 6, rcY2, 7.5, bold)
    text(ticket.effectiveDate || 'September 12, 2025', refX + refLabelW + 6, rcY2, 7.5, regular)

    // row 3: Rev.No | value | Page No.: | value
    const row3Top = headerTop - refRowH * 2
    const revValW = (refColW - refLabelW) * 0.28
    const pageLabelW = (refColW - refLabelW) * 0.38
    const revValX = refX + refLabelW
    const pageLabelX = revValX + revValW
    const pageValX = pageLabelX + pageLabelW
    vLine(revValX, headerBottom, row3Top)
    vLine(pageLabelX, headerBottom, row3Top)
    const rcY3 = row3Top - refRowH / 2 - 3
    text('Rev. No.', refX + 6, rcY3, 7.5, bold)
    centered(String(ticket.revNo ?? '1'), revValX + revValW / 2, rcY3, 7.5, regular)
    text('Page No.:', pageLabelX + 6, rcY3, 7.5, bold)
    centered('1 of 1', pageValX + (refX + refColW - pageValX) / 2, rcY3, 7.5, regular)

    // agency title column: row1 (agency name, 2 lines) / row2 (form title)
    const titleRow1H = refRowH * 2
    const titleCx = titleX + titleColW / 2
    hLine(titleX, refX, headerTop - titleRow1H)
    centered('BUREAU OF SOILS AND WATER MANAGEMENT', titleCx, headerTop - titleRow1H / 2 - 2, 9.5, bold)
    centered('Laboratory Services Division', titleCx, headerTop - titleRow1H / 2 - 15, 9, bold)
    centered('LIMS HELPDESK REQUEST FORM', titleCx, headerBottom + refRowH / 2 - 3, 10.5, bold)

    y = headerBottom - 8

    // ══════════════════════════════════════════════════════════
    // TOP META TABLE — ISSUE / TICKET NO / STATUS / URGENCY / TARGET
    // Layout (matches source form):
    //   row1: ISSUE .......................................... (full width)
    //   row2: TICKET NO   | DATA SUBMITTED  ┐ (merged, spans rows 2-3)
    //   row3: TICKET STATUS |               ┘
    //   row4: URGENCY LEVEL | RESOLUTION TARGET
    // ══════════════════════════════════════════════════════════
    const metaTop = y
    const metaH = ROW_H * 4
    const metaBottom = metaTop - metaH
    const midX = MARGIN + CONTENT_W / 2
    const leftLabelW = 108
    const rightLabelW = 118

    strokeRect(MARGIN, metaBottom, CONTENT_W, metaH)
    hLine(MARGIN, MARGIN + CONTENT_W, metaTop - ROW_H)                 // below ISSUE
    hLine(MARGIN, midX, metaTop - ROW_H * 2)                           // TICKET NO / TICKET STATUS divider (left only)
    hLine(MARGIN, MARGIN + CONTENT_W, metaTop - ROW_H * 3)             // below TICKET STATUS row (full width)
    vLine(midX, metaTop - ROW_H, metaTop - ROW_H * 4)                  // column divider, rows 2-4

    // row 1: ISSUE (full width, navy label)
    navyLabel('ISSUE:', MARGIN, metaTop - ROW_H, leftLabelW, ROW_H)
    text(f.ISSUE, MARGIN + leftLabelW + 6, metaTop - 11, 9, regular)

    // row 2: TICKET NO | DATA SUBMITTED (value cell merged across rows 2-3)
    navyLabel('TICKET NO:', MARGIN, metaTop - ROW_H * 2, leftLabelW, ROW_H)
    text(f.TICKET_ID, MARGIN + leftLabelW + 6, metaTop - ROW_H - 11, 9, regular)
    navyLabel('DATA SUBMITTED:', midX, metaTop - ROW_H * 2, rightLabelW, ROW_H)
    text(f.DATE_CREATED, midX + rightLabelW + 6, metaTop - ROW_H - 11, 9, regular)

    // row 3: TICKET STATUS (right cell stays blank — merged with row 2 above)
    navyLabel('TICKET STATUS:', MARGIN, metaTop - ROW_H * 3, leftLabelW, ROW_H)
    text(f.NEW_STATUS, MARGIN + leftLabelW + 6, metaTop - ROW_H * 2 - 11, 9, regular)

    // row 4: URGENCY LEVEL | RESOLUTION TARGET
    navyLabel('URGENCY LEVEL:', MARGIN, metaTop - ROW_H * 4, leftLabelW, ROW_H)
    text(f.URGENCY_LEVEL, MARGIN + leftLabelW + 6, metaTop - ROW_H * 3 - 11, 9, regular)
    navyLabel('RESOLUTION TARGET:', midX, metaTop - ROW_H * 4, rightLabelW, ROW_H)
    text(f.TARGET_RESOLUTION, midX + rightLabelW + 6, metaTop - ROW_H * 3 - 11, 9, regular)

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

    // description block, attached under end-user rows, with the narrow
    // attachment-icon column on the right (matches the source form).
    const descTop = euRowsTop - euRowsH
    const descTextW = CONTENT_W - ICON_COL_W
    navyBar(MARGIN, descTop - BAR_H, CONTENT_W, BAR_H)
    text('DESCRIPTION/DETAILS OF THE REQUIREMENT', MARGIN + 6, descTop - BAR_H + 4.5, 8, bold, WHITE)
    const descBoxTop = descTop - BAR_H
    strokeRect(MARGIN, descBoxTop - DESC_H, CONTENT_W, DESC_H)
    vLine(MARGIN + descTextW, descBoxTop - DESC_H, descBoxTop)
    drawPaperclipIcon(MARGIN + descTextW + ICON_COL_W / 2 - 3, descTop - BAR_H + 4, 0.8)
    let dy = descBoxTop - 12
    for (const line of wrapText(f.PLEASE_SPECIFY, regular, 9, descTextW - 12)) {
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

    // row 1: RESPONSIBLE PERSON (full width)
    const respRowTop = resTop - BAR_H
    strokeRect(MARGIN, respRowTop - ROW_H, CONTENT_W, ROW_H)
    text('RESPONSIBLE PERSON:', MARGIN + 6, respRowTop - 11, 8, bold)
    text(f.ASSIGNED_TO, MARGIN + 130, respRowTop - 11, 9, regular)

    // row 2: DATE OF ACTION | DATE CLOSE
    const dateRowTop = respRowTop - ROW_H
    strokeRect(MARGIN, dateRowTop - ROW_H, CONTENT_W, ROW_H)
    vLine(midX, dateRowTop - ROW_H, dateRowTop)
    text('DATE OF ACTION:', MARGIN + 6, dateRowTop - 11, 8, bold)
    text(f.DATE_ACTION, MARGIN + 100, dateRowTop - 11, 9, regular)
    text('DATE CLOSE:', midX + 6, dateRowTop - 11, 8, bold)
    text(f.DATE_CLOSE, midX + 80, dateRowTop - 11, 9, regular)

    const detailTop = dateRowTop - ROW_H
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

/** Embeds the shared header logo once per PDFDocument (call before drawing pages). */
async function embedLogo(pdfDoc) {
    try {
        return await pdfDoc.embedPng(base64ToUint8Array(LOGO_PNG_BASE64))
    } catch (err) {
        console.warn('TicketExport: failed to embed header logo, continuing without it.', err)
        return null
    }
}

/** Render one ticket into a single-page PDF and return the bytes. */
export async function renderTicketPdf(ticket) {
    const pdfDoc = await PDFDocument.create()
    const regular = await pdfDoc.embedFont(StandardFonts.Helvetica)
    const bold = await pdfDoc.embedFont(StandardFonts.HelveticaBold)
    const logoImage = await embedLogo(pdfDoc)
    await drawTicketPage(pdfDoc, ticket, { regular, bold }, logoImage)
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
    const logoImage = await embedLogo(pdfDoc)
    for (const ticket of tickets) {
        await drawTicketPage(pdfDoc, ticket, { regular, bold }, logoImage)
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