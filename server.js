const http = require(‘http’);
const fs = require(‘fs’);
const path = require(‘path’);

const PORT = process.env.PORT || 3000;

const mimeTypes = {
‘.html’: ‘text/html’,
‘.js’:   ‘application/javascript’,
‘.css’:  ‘text/css’,
‘.png’:  ‘image/png’,
‘.jpg’:  ‘image/jpeg’,
‘.svg’:  ‘image/svg+xml’,
‘.json’: ‘application/json’,
‘.ico’:  ‘image/x-icon’
};

const server = http.createServer((req, res) => {
let filePath = path.join(__dirname, req.url === ‘/’ ? ‘index.html’ : req.url);

fs.readFile(filePath, (err, data) => {
if (err) {
// Try index.html as fallback
fs.readFile(path.join(__dirname, ‘index.html’), (err2, data2) => {
if (err2) {
res.writeHead(404);
res.end(‘Not found’);
} else {
res.writeHead(200, { ‘Content-Type’: ‘text/html’ });
res.end(data2);
}
});
return;
}
const ext = path.extname(filePath);
const contentType = mimeTypes[ext] || ‘application/octet-stream’;
res.writeHead(200, { ‘Content-Type’: contentType });
res.end(data);
});
});

server.listen(PORT, () => {
console.log(’Server running on port ’ + PORT);
});
