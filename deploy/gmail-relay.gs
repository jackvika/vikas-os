/**
 * Vikas OS: Gmail relay (Google Apps Script)
 *
 * Lets the website send email through YOUR Gmail over HTTPS, which works on hosts
 * that block SMTP (e.g. Render free plan). Free quota: ~100 emails/day.
 *
 * SETUP (one time, ~5 minutes):
 *  1. Go to https://script.google.com → New project → paste this whole file.
 *  2. Project Settings (gear icon) → Script Properties → Add property:
 *       RELAY_SECRET = <a long random secret; the same value goes into Render as GMAIL_RELAY_SECRET>
 *  3. Run the function "authorize" once (▶ Run) and allow the permissions.
 *  4. Deploy → New deployment → type "Web app"
 *       Execute as: Me      Who has access: Anyone
 *     Copy the Web app URL (…/exec) → Render env GMAIL_RELAY_URL.
 *
 * After editing this code later: Deploy → Manage deployments → Edit → Version: New version.
 */

function doPost(e) {
  try {
    var data = JSON.parse((e && e.postData && e.postData.contents) || '{}');
    var secret = PropertiesService.getScriptProperties().getProperty('RELAY_SECRET');
    if (!secret || !data.secret || data.secret !== secret) {
      return json_({ ok: false, error: 'unauthorized' });
    }
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
  return json_({ ok: true, service: 'vikas-gmail-relay' });
}

/** Run once from the editor to grant the "send email" permission. */
function authorize() {
  Logger.log('Emails left today: ' + MailApp.getRemainingDailyQuota());
}

function json_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
