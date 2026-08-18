package com.navracar.mobile;

import android.content.Context;
import android.content.SharedPreferences;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import android.webkit.JavascriptInterface;
import java.nio.ByteBuffer;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import java.security.MessageDigest;
import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

final class SecureStoreBridge {
    private static final String ALIAS = "navracar_mobile_secure_store_v1";
    private final SharedPreferences preferences;

    SecureStoreBridge(Context context) {
        preferences = context.getSharedPreferences("navracar_secure", Context.MODE_PRIVATE);
    }

    @JavascriptInterface
    public String getItem(String key) {
        try {
            String encoded = preferences.getString(keyName(key), null);
            if (encoded == null) return null;
            byte[] payload = Base64.decode(encoded, Base64.NO_WRAP);
            ByteBuffer buffer = ByteBuffer.wrap(payload);
            int ivSize = buffer.getInt();
            if (ivSize < 12 || ivSize > 32 || buffer.remaining() <= ivSize) return null;
            byte[] iv = new byte[ivSize];
            buffer.get(iv);
            byte[] encrypted = new byte[buffer.remaining()];
            buffer.get(encrypted);
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.DECRYPT_MODE, key(), new GCMParameterSpec(128, iv));
            return new String(cipher.doFinal(encrypted), StandardCharsets.UTF_8);
        } catch (Exception ignored) {
            return null;
        }
    }

    @JavascriptInterface
    public void setItem(String key, String value) {
        if (value == null) { removeItem(key); return; }
        try {
            Cipher cipher = Cipher.getInstance("AES/GCM/NoPadding");
            cipher.init(Cipher.ENCRYPT_MODE, key());
            byte[] encrypted = cipher.doFinal(value.getBytes(StandardCharsets.UTF_8));
            byte[] iv = cipher.getIV();
            ByteBuffer payload = ByteBuffer.allocate(4 + iv.length + encrypted.length);
            payload.putInt(iv.length).put(iv).put(encrypted);
            preferences.edit().putString(keyName(key), Base64.encodeToString(payload.array(), Base64.NO_WRAP)).apply();
        } catch (Exception ignored) {
            // Do not fall back to plaintext or log secret material.
        }
    }

    @JavascriptInterface
    public void removeItem(String key) {
        preferences.edit().remove(keyName(key)).apply();
    }

    private SecretKey key() throws Exception {
        KeyStore store = KeyStore.getInstance("AndroidKeyStore");
        store.load(null);
        if (store.containsAlias(ALIAS)) return (SecretKey) store.getKey(ALIAS, null);
        KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        generator.init(new KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build());
        return generator.generateKey();
    }

    private String keyName(String key) {
        try {
            byte[] digest = MessageDigest.getInstance("SHA-256").digest(String.valueOf(key).getBytes(StandardCharsets.UTF_8));
            return Base64.encodeToString(digest, Base64.NO_WRAP | Base64.URL_SAFE);
        } catch (Exception ignored) {
            return "invalid";
        }
    }
}
