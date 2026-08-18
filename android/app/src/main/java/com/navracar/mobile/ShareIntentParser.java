package com.navracar.mobile;

import java.util.regex.Matcher;
import java.util.regex.Pattern;

final class ShareIntentParser {
    private static final Pattern HTTPS_URL = Pattern.compile("https://[^\\s<>\\\"]+", Pattern.CASE_INSENSITIVE);

    private ShareIntentParser() {}

    static String firstHttpsUrl(String text) {
        if (text == null) return null;
        Matcher matcher = HTTPS_URL.matcher(text.trim());
        return matcher.find() ? matcher.group() : null;
    }
}
