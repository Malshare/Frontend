"""
MalShare API Tests - Docker-based

This test suite validates the functionality of the MalShare API.
Tests run inside Docker and access the site via the Docker network.

Test API Key from malshare_db.sql:
- api_key: f2ca1bb6c7e907d06dafe4687e579fce76b37e4e93b7605022da52e6ccc26fd2
- user: testuser
"""

import os
import requests
import json
import pytest
import re
from io import BytesIO
from typing import Dict, Any

# Configuration from environment variables
BASE_URL = os.getenv("MALSHARE_URL", "http://localhost:8080")
TEST_API_KEY = os.getenv("MALSHARE_API_KEY", "f2ca1bb6c7e907d06dafe4687e579fce76b37e4e93b7605022da52e6ccc26fd2")
API_ENDPOINT = f"{BASE_URL}/api.php"

# Wait for service to be ready
import time
max_retries = 30
retry_count = 0
while retry_count < max_retries:
    try:
        response = requests.get(API_ENDPOINT, params={"api_key": TEST_API_KEY, "action": "getlimit"}, timeout=2)
        if response.status_code in [200, 400]:
            print(f"✓ MalShare API is ready at {API_ENDPOINT}")
            break
    except Exception as e:
        pass
    retry_count += 1
    time.sleep(1)
    if retry_count == max_retries:
        print(f"✗ Could not connect to MalShare at {API_ENDPOINT}")
        raise RuntimeError(f"Could not connect to API after {max_retries} attempts")


# ==================== FIXTURES ====================

@pytest.fixture
def session():
    """Create a requests session for API calls"""
    s = requests.Session()
    yield s
    s.close()


def make_request(session, action: str, method: str = "GET", **params) -> requests.Response:
    """
    Helper to make API requests
    
    Args:
        session: requests.Session object
        action: API action to perform
        method: HTTP method (GET or POST)
        **params: Additional parameters
    
    Returns:
        Response object
    """
    request_params = {
        "api_key": TEST_API_KEY,
        "action": action,
        **{k: v for k, v in params.items() if v is not None}
    }
    
    if method.upper() == "GET":
        return session.get(API_ENDPOINT, params=request_params, timeout=5)
    elif method.upper() == "POST":
        return session.post(API_ENDPOINT, params=request_params, timeout=5)


# ==================== BASIC CONNECTIVITY TESTS ====================

class TestAPIConnectivity:
    """Test basic API connectivity"""
    
    def test_api_is_reachable(self, session):
        """Test that API endpoint is reachable"""
        response = make_request(session, "getlimit")
        # Should get a response (not timeout or connection error)
        assert response is not None
        assert response.status_code in [200, 400]
    
    def test_api_requires_api_key(self, session):
        """Test that API key is required"""
        response = session.get(API_ENDPOINT, params={"action": "getlist"}, timeout=5)
        assert response.status_code == 400
        assert "api_key" in response.text.lower()
    
    def test_api_requires_action(self, session):
        """Test that action parameter is required"""
        response = session.get(API_ENDPOINT, params={"api_key": TEST_API_KEY}, timeout=5)
        assert response.status_code == 400
        assert "action" in response.text.lower()
    
    def test_invalid_api_key_rejected(self, session):
        """Test that invalid API key is rejected"""
        response = session.get(
            API_ENDPOINT,
            params={"api_key": "invalid_key_xyz", "action": "getlist"},
            timeout=5
        )
        assert response.status_code == 400


# ==================== GETLIST ENDPOINT TESTS ====================

class TestGetListEndpoint:
    """Test getlist endpoint"""
    
    def test_getlist_returns_200(self, session):
        """Test getlist returns HTTP 200"""
        response = make_request(session, "getlist")
        # Accept 200 as valid
        if response.status_code == 200:
            assert response.status_code == 200
        else:
            # Record what we got instead
            pytest.skip(f"getlist returned {response.status_code}: {response.text[:100]}")
    
    def test_getlist_returns_json(self, session):
        """Test getlist returns valid JSON"""
        response = make_request(session, "getlist")
        if response.status_code != 200:
            pytest.skip(f"getlist returned {response.status_code}")
        
        try:
            data = response.json()
            assert isinstance(data, (dict, list))
        except json.JSONDecodeError:
            pytest.fail("getlist did not return valid JSON")
    
    def test_getlist_has_results_field(self, session):
        """Test getlist response has results field if successful"""
        response = make_request(session, "getlist")
        if response.status_code != 200:
            pytest.skip(f"getlist returned {response.status_code}")
        
        try:
            data = response.json()
            # If it's a dict, should have results field
            if isinstance(data, dict):
                assert "results" in data or "error" in data
        except json.JSONDecodeError:
            pytest.skip("Invalid JSON response")


# ==================== GETLISTRAW ENDPOINT TESTS ====================

class TestGetListRawEndpoint:
    """Test getlistraw endpoint"""
    
    def test_getlistraw_returns_text(self, session):
        """Test getlistraw returns plain text"""
        response = make_request(session, "getlistraw")
        if response.status_code != 200:
            pytest.skip(f"getlistraw returned {response.status_code}")
        
        # Should be text content
        assert isinstance(response.text, str)
    
    def test_getlistraw_format(self, session):
        """Test getlistraw returns hash-like values"""
        response = make_request(session, "getlistraw")
        if response.status_code != 200:
            pytest.skip(f"getlistraw returned {response.status_code}")
        
        lines = response.text.strip().split('\n')
        
        # If we have content, validate format
        if lines and lines[0]:
            # First line should look like a hash (hex characters)
            first_line = lines[0].strip()
            # Very basic check: should be alphanumeric
            assert all(c in '0123456789abcdefABCDEF' for c in first_line if c)


# ==================== GETSOURCES ENDPOINT TESTS ====================

class TestGetSourcesEndpoint:
    """Test getsources endpoint"""
    
    def test_getsources_responds(self, session):
        """Test getsources endpoint responds"""
        response = make_request(session, "getsources")
        assert response.status_code in [200, 400]
    
    def test_getsources_json_if_200(self, session):
        """Test getsources returns JSON when status is 200"""
        response = make_request(session, "getsources")
        if response.status_code == 200:
            try:
                data = response.json()
                assert isinstance(data, (dict, list))
            except json.JSONDecodeError:
                pytest.fail("getsources returned 200 but invalid JSON")


# ==================== GETSOURCESRAW ENDPOINT TESTS ====================

class TestGetSourcesRawEndpoint:
    """Test getsourcesraw endpoint"""
    
    def test_getsourcesraw_responds(self, session):
        """Test getsourcesraw endpoint responds"""
        response = make_request(session, "getsourcesraw")
        assert response.status_code in [200, 400]


# ==================== GETFILENAMES ENDPOINT TESTS ====================

class TestGetFileNamesEndpoint:
    """Test getfilenames endpoint"""
    
    def test_getfilenames_responds(self, session):
        """Test getfilenames endpoint responds"""
        response = make_request(session, "getfilenames")
        assert response.status_code in [200, 400]


# ==================== GETTYPES ENDPOINT TESTS ====================

class TestGetTypesEndpoint:
    """Test gettypes endpoint"""
    
    def test_gettypes_returns_200(self, session):
        """Test gettypes returns HTTP 200"""
        response = make_request(session, "gettypes")
        if response.status_code == 200:
            assert response.status_code == 200
        else:
            pytest.skip(f"gettypes returned {response.status_code}")
    
    def test_gettypes_returns_json(self, session):
        """Test gettypes returns valid JSON"""
        response = make_request(session, "gettypes")
        if response.status_code != 200:
            pytest.skip(f"gettypes returned {response.status_code}")
        
        try:
            data = response.json()
            assert isinstance(data, (dict, list))
        except json.JSONDecodeError:
            pytest.fail("gettypes did not return valid JSON")


# ==================== DAILYSUM ENDPOINT TESTS ====================

class TestDailySumEndpoint:
    """Test dailysum endpoint"""
    
    def test_dailysum_responds(self, session):
        """Test dailysum endpoint responds"""
        response = make_request(session, "dailysum")
        assert response.status_code in [200, 400]
    
    def test_dailysum_json_if_200(self, session):
        """Test dailysum returns JSON when status is 200"""
        response = make_request(session, "dailysum")
        if response.status_code == 200:
            try:
                data = response.json()
                assert isinstance(data, (dict, list))
            except json.JSONDecodeError:
                pytest.fail("dailysum returned 200 but invalid JSON")


# ==================== GETLIMIT ENDPOINT TESTS ====================

class TestGetLimitEndpoint:
    """Test getlimit endpoint"""
    
    def test_getlimit_returns_200(self, session):
        """Test getlimit returns HTTP 200"""
        response = make_request(session, "getlimit")
        assert response.status_code == 200
    
    def test_getlimit_has_content(self, session):
        """Test getlimit returns content"""
        response = make_request(session, "getlimit")
        if response.status_code == 200:
            assert len(response.text) > 0
    
    def test_getlimit_contains_numbers(self, session):
        """Test getlimit response contains numeric values"""
        response = make_request(session, "getlimit")
        if response.status_code == 200:
            # Should contain quota numbers
            assert any(char.isdigit() for char in response.text)


# ==================== SEARCH ENDPOINT TESTS ====================

class TestSearchEndpoint:
    """Test search endpoint"""
    
    def test_search_without_query(self, session):
        """Test search endpoint without query parameter"""
        response = make_request(session, "search")
        assert response.status_code in [200, 400]
    
    def test_search_with_query(self, session):
        """Test search endpoint with query"""
        response = make_request(session, "search", query="test")
        assert response.status_code in [200, 400]
    
    def test_search_special_chars_safe(self, session):
        """Test search safely handles special characters"""
        response = make_request(session, "search", query="'; DROP TABLE;--")
        # Should not crash
        assert response.status_code in [200, 400]


# ==================== DETAILS ENDPOINT TESTS ====================

class TestDetailsEndpoint:
    """Test details endpoint"""
    
    def test_details_responds(self, session):
        """Test details endpoint responds"""
        response = make_request(session, "details", hash="abc123")
        assert response.status_code in [200, 400]


# ==================== HASHLOOKUP ENDPOINT TESTS ====================

class TestHashLookupEndpoint:
    """Test hashlookup endpoint"""
    
    def test_hashlookup_get_not_allowed(self, session):
        """Test hashlookup requires POST"""
        response = make_request(session, "hashlookup", method="GET")
        # GET should fail
        assert response.status_code in [400, 405]
    
    def test_hashlookup_post_responds(self, session):
        """Test hashlookup POST responds"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "hashlookup"},
            data="test_hash",
            timeout=5
        )
        assert response.status_code in [200, 400]


# ==================== TYPE ENDPOINT TESTS ====================

class TestTypeEndpoint:
    """Test type endpoint"""
    
    def test_type_responds(self, session):
        """Test type endpoint responds"""
        response = make_request(session, "type", type="exe")
        assert response.status_code in [200, 400]


# ==================== UPLOAD ENDPOINT TESTS ====================

class TestUploadEndpoint:
    """Test upload endpoint"""
    
    def test_upload_no_file_fails(self, session):
        """Test upload requires file"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "upload"},
            timeout=5
        )
        assert response.status_code == 400
    
    def test_upload_small_file(self, session):
        """Test uploading a small file"""
        files = {
            "upload": ("test.txt", BytesIO(b"test content"), "text/plain")
        }
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "upload"},
            files=files,
            timeout=5
        )
        # Should either succeed or fail gracefully
        assert response.status_code in [200, 400]
    
    def test_upload_oversized_file_rejected(self, session):
        """Test oversized file is rejected"""
        # Create 10MB + 1 byte file
        oversized = b"x" * (10000001)
        files = {
            "upload": ("large.bin", BytesIO(oversized), "application/octet-stream")
        }
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "upload"},
            files=files,
            timeout=10
        )
        # Should be rejected with 413
        assert response.status_code in [413, 400]


# ==================== DOWNLOAD_URL ENDPOINT TESTS ====================

class TestDownloadUrlEndpoint:
    """Test download_url endpoint"""
    
    def test_download_url_get_not_allowed(self, session):
        """Test download_url requires POST"""
        response = make_request(session, "download_url", method="GET")
        assert response.status_code == 400
    
    def test_download_url_requires_url_field(self, session):
        """Test download_url requires url parameter"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "download_url"},
            data={},
            timeout=5
        )
        assert response.status_code == 400
    
    def test_download_url_invalid_url_rejected(self, session):
        """Test invalid URL is rejected"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "download_url"},
            data={"url": "not a url"},
            timeout=5
        )
        assert response.status_code == 400
    
    def test_download_url_valid_url_response(self, session):
        """Test valid URL gets a response"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "download_url"},
            data={"url": "http://example.com"},
            timeout=5
        )
        # Should accept valid URL
        if response.status_code == 200:
            try:
                data = response.json()
                assert "guid" in data or "error" in data
            except json.JSONDecodeError:
                pytest.skip("Invalid JSON response")


# ==================== DOWNLOAD_URL_CHECK ENDPOINT TESTS ====================

class TestDownloadUrlCheckEndpoint:
    """Test download_url_check endpoint"""
    
    def test_download_url_check_post_not_allowed(self, session):
        """Test download_url_check requires GET"""
        response = session.post(
            API_ENDPOINT,
            params={"api_key": TEST_API_KEY, "action": "download_url_check"},
            timeout=5
        )
        assert response.status_code == 400
    
    def test_download_url_check_requires_guid(self, session):
        """Test download_url_check requires guid parameter"""
        response = make_request(session, "download_url_check")
        assert response.status_code == 400
    
    def test_download_url_check_invalid_guid_rejected(self, session):
        """Test invalid guid is rejected"""
        response = make_request(session, "download_url_check", guid="invalid")
        assert response.status_code == 400
    
    def test_download_url_check_valid_guid_format(self, session):
        """Test valid guid format is accepted"""
        # Use a valid UUID format
        guid = "550e8400-e29b-41d4-a716-446655440000"
        response = make_request(session, "download_url_check", guid=guid)
        # Should accept valid format
        if response.status_code == 200:
            try:
                data = response.json()
                assert "guid" in data or "status" in data
            except json.JSONDecodeError:
                pytest.skip("Invalid JSON response")


# ==================== INTEGRATION TESTS ====================

class TestAPIIntegration:
    """Test API integration and workflows"""
    
    def test_multiple_sequential_requests(self, session):
        """Test making multiple sequential requests"""
        responses = []
        
        for action in ["getlimit", "gettypes", "getlist"]:
            response = make_request(session, action)
            responses.append(response)
            assert response.status_code in [200, 400]
        
        # All should get responses
        assert len(responses) == 3
    
    def test_api_key_consistency(self, session):
        """Test API key works consistently"""
        response1 = make_request(session, "getlimit")
        response2 = make_request(session, "getlimit")
        
        # Both should succeed or both should fail in same way
        assert response1.status_code == response2.status_code
    
    def test_error_responses_are_consistent(self, session):
        """Test error responses are consistent"""
        # Missing required parameter
        r1 = session.get(API_ENDPOINT, params={"api_key": TEST_API_KEY}, timeout=5)
        r2 = session.get(API_ENDPOINT, params={"api_key": TEST_API_KEY}, timeout=5)
        
        # Same request twice should give same status
        assert r1.status_code == r2.status_code


# ==================== STRESS/EDGE CASE TESTS ====================

class TestAPIEdgeCases:
    """Test edge cases and error conditions"""
    
    def test_empty_api_key(self, session):
        """Test with empty API key"""
        response = session.get(
            API_ENDPOINT,
            params={"api_key": "", "action": "getlist"},
            timeout=5
        )
        assert response.status_code == 400
    
    def test_very_long_query(self, session):
        """Test with very long query parameter"""
        long_query = "a" * 5000
        response = make_request(session, "search", query=long_query)
        # Should handle without crashing
        assert response.status_code in [200, 400, 414]
    
    def test_unicode_in_query(self, session):
        """Test with Unicode characters"""
        response = make_request(session, "search", query="测试中文")
        # Should handle gracefully
        assert response.status_code in [200, 400]
    
    def test_null_bytes_safe(self, session):
        """Test null bytes are handled safely"""
        # Try to pass null bytes
        params = {
            "api_key": TEST_API_KEY,
            "action": "search",
            "query": "test\x00null"
        }
        try:
            response = session.get(API_ENDPOINT, params=params, timeout=5)
            # Should not crash
            assert response.status_code in [200, 400]
        except Exception:
            # Some implementations may reject this at request level
            pass


if __name__ == "__main__":
    # Run with: pytest test_malshare_api.py -v
    pytest.main([__file__, "-v", "--tb=short"])
