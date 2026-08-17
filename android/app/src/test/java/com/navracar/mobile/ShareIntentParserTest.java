package com.navracar.mobile;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

import org.junit.Test;

public class ShareIntentParserTest {
    @Test
    public void extractsFirstHttpsUrlFromSharedText() {
        assertEquals(
            "https://dubai.dubizzle.com/motors/used-cars/bmw/x5/123",
            ShareIntentParser.firstHttpsUrl("این خودرو را ببین https://dubai.dubizzle.com/motors/used-cars/bmw/x5/123 ممنون")
        );
    }

    @Test
    public void rejectsCleartextAndNonUrlText() {
        assertNull(ShareIntentParser.firstHttpsUrl("http://127.0.0.1/private"));
        assertNull(ShareIntentParser.firstHttpsUrl("BMW X5 without a link"));
    }
}
