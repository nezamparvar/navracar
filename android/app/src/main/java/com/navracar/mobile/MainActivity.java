package com.navracar.mobile;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    private final ShareBridge shareBridge = new ShareBridge();

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        getBridge().getWebView().addJavascriptInterface(new SecureStoreBridge(this), "NavraSecureStore");
        getBridge().getWebView().addJavascriptInterface(shareBridge, "NavraShare");
        acceptShare(getIntent());
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        setIntent(intent);
        acceptShare(intent);
    }

    private void acceptShare(Intent intent) {
        if (intent == null) return;
        String url = null;
        if (Intent.ACTION_SEND.equals(intent.getAction()) && "text/plain".equals(intent.getType())) {
            url = ShareIntentParser.firstHttpsUrl(intent.getStringExtra(Intent.EXTRA_TEXT));
        } else if (Intent.ACTION_VIEW.equals(intent.getAction())) {
            Uri data = intent.getData();
            if (data != null) url = ShareIntentParser.firstHttpsUrl(data.getQueryParameter("url"));
        }
        if (url == null) return;
        shareBridge.setPendingUrl(url);
        if (getBridge() != null) getBridge().getWebView().post(() ->
            getBridge().getWebView().evaluateJavascript("window.dispatchEvent(new Event('navracar:share'))", null)
        );
    }
}
