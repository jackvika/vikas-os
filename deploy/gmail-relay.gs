/**
 * Vikas OS: Gmail relay + Leads sheet (Google Apps Script)
 *
 * 1) Sends the website's emails through YOUR Gmail over HTTPS, which works on hosts
 *    that block SMTP (e.g. Render free plan). Free quota: ~100 emails/day.
 * 2) Saves every inquiry / interview-call request as a row in a Google Sheet
 *    ("Vikas OS – Leads"), created automatically in your Google Drive.
 *
 * SETUP (one time, ~5 minutes):
 *  1. Go to https://script.google.com → New project → paste this whole file.
 *  2. Project Settings (gear icon) → Script Properties → Add property:
 *       RELAY_SECRET = <a long random secret; the same value goes into Render as GMAIL_RELAY_SECRET>
 *  3. Run the function "authorize" once (▶ Run) and allow the permissions
 *     (send email + Google Sheets). The log shows your Leads sheet link.
 *  4. Deploy → New deployment → type "Web app"
 *       Execute as: Me      Who has access: Anyone
 *     Copy the Web app URL (…/exec) → Render env GMAIL_RELAY_URL.
 *
 * UPDATING this code later (e.g. after pasting a new version):
 *   Run "authorize" again if new permissions are needed, then
 *   Deploy → Manage deployments → ✏️ Edit → Version: "New version" → Deploy.
 *   (The /exec URL stays the same, so Render needs no change.)
 */

var SHEET_NAME = 'Vikas OS – Leads';
var HEADERS = ['Received', 'Type', 'Name', 'Email', 'Phone', 'Company', 'Preferred slot', 'Message', 'Source', 'Status'];

function doPost(e) {
  try {
    var data = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    var secret = PropertiesService.getScriptProperties().getProperty('RELAY_SECRET');
    if (!secret || !data.secret || data.secret !== secret) {
      return json_({ ok: false, error: 'unauthorized' });
    }

    if (data.action === 'lead') {
      var url = appendLead_(data.lead || {});
      return json_({ ok: true, sheet: url });
    }
    if (data.action && data.action !== 'send') {
      return json_({ ok: false, error: 'unknown action' });
    }

    // Default action: send an email
    if (!data.to || !data.subject) {
      return json_({ ok: false, error: 'missing to/subject' });
    }
    var options = {
      to: String(data.to),
      subject: String(data.subject).slice(0, 250),
      body: String(data.text || ''),
      htmlBody: String(data.html || ''),
      name: String(data.fromName || 'Vikas Rambaul Giri')
    };
    if (data.replyTo) { options.replyTo = String(data.replyTo); }
    MailApp.sendEmail(options);
    return json_({ ok: true, remaining: MailApp.getRemainingDailyQuota() });
  } catch (err) {
    return json_({ ok: false, error: String(err) });
  }
}

/** Health check: open the /exec URL in a browser → {"ok":true,"service":"vikas-gmail-relay"} */
function doGet() {
  return json_({ ok: true, service: 'vikas-gmail-relay', leads: true });
}

/** Run once from the editor: grants permissions and creates the Leads sheet. */
function authorize() {
  Logger.log('Emails left today: ' + MailApp.getRemainingDailyQuota());
  Logger.log('Leads sheet: ' + leadsSheet_().getParent().getUrl());
}

/** Append one lead row (newest at the bottom). Returns the spreadsheet URL. */
function appendLead_(lead) {
  var sheet = leadsSheet_();
  var row = [
    lead.date, lead.type, lead.name, lead.email, lead.phone, lead.company,
    lead.slot, lead.message, lead.source, 'New'
  ].map(safeCell_);
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    sheet.appendRow(row);
  } finally {
    lock.releaseLock();
  }
  return sheet.getParent().getUrl();
}

/** Opens the Leads spreadsheet, creating it (with a header row) on first use. */
function leadsSheet_() {
  var props = PropertiesService.getScriptProperties();
  var id = props.getProperty('SHEET_ID');
  var ss = null;
  if (id) {
    try { ss = SpreadsheetApp.openById(id); } catch (err) { ss = null; }
  }
  if (!ss) {
    ss = SpreadsheetApp.create(SHEET_NAME);
    props.setProperty('SHEET_ID', ss.getId());
  }
  var sheet = ss.getSheets()[0];
  if (sheet.getLastRow() === 0) {
    sheet.setName('Leads');
    sheet.appendRow(HEADERS);
    sheet.getRange(1, 1, 1, HEADERS.length).setFontWeight('bold').setBackground('#E8F0FE');
    sheet.setFrozenRows(1);
    sheet.setColumnWidth(8, 420);
  }
  return sheet;
}

/** Plain text only: prefix values a spreadsheet would treat as a formula. */
function safeCell_(v) {
  var s = (v === undefined || v === null) ? '' : String(v).slice(0, 5000);
  return /^[=+\-@]/.test(s) ? "'" + s : s;
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
