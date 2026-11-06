"""Serve the prototype with caching off, so every edit shows on the next reload.
Usage: py UIUX/v2/tools/serve.py [port]"""
import sys
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path


class NoCache(SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header("Cache-Control", "no-store")
        super().end_headers()


root = Path(__file__).resolve().parent.parent / "prototype"
port = int(sys.argv[1]) if len(sys.argv) > 1 else 8731
ThreadingHTTPServer(("127.0.0.1", port), partial(NoCache, directory=str(root))).serve_forever()
