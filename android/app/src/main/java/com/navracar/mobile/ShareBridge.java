package com.navracar.mobile;

import android.webkit.JavascriptInterface;

final class ShareBridge {
    private String pendingUrl;

    synchronized void setPendingUrl(String url) {
        pendingUrl = url;
    }

    @JavascriptInterface
    public synchronized String consume() {
        String value = pendingUrl;
        pendingUrl = null;
        return value;
    }
}
