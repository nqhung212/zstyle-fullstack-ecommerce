#include <iostream> 
#include <cmath>

using namespace std;

int solveQuartic(double a, double b, double c, double x[]) {
    if (a == 0 && b == 0 && c == 0) {
        return -1;
    }

    if (a == 0 && b == 0) {
        return 0;
    }

    if (a == 0) {
        double y = -c / b; if (y < 0) return 0; x[0] = sqrt(y);
        x[1] = -sqrt(y); return 2;
    }

    double delta = b * b - 4 * a * c; if (delta < 0) return 0;

    double y1 = (-b + sqrt(delta)) / (2 * a); double y2 = (-b - sqrt(delta)) / (2 * a);

    int count = 0; if (y1 >= 0) {
        x[count++] = sqrt(y1); x[count++] = -sqrt(y1);
    }
    if (y2 >= 0 && y2 != y1) {
        x[count++] = sqrt(y2); x[count++] = -sqrt(y2);
    }

    return count;
}
void runTest(double a, double b, double c, const string& expected) {
    double x[4];
    
    int n = solveQuartic(a, b, c, x);
    cout << "Test (" << a << ", " << b << ", " << c << ") : ";
    if (n == -1) {
        cout << "Infinite solutions.";
    }
    else if (n == 0) {
        cout << "No solution.";
    }
    else {
        cout << "The equation has " << n << " real solution(s): ";
        for (int i = 0; i < n; i++) cout << x[i] << " ";
    }
    cout << "\nExpected: " << expected << "\n\n";
}

int main() {
    runTest(0, 0, 0, "Infinite solutions.");
    runTest(0, 0, 1, "No solution.");
    runTest(0, 1, 2, "No solution.");
    runTest(0, 1, -2, "The equation has 2 real solution(s): sqrt(2), -sqrt(2)");
    runTest(1, 1, 1, "No solution.");
    runTest(1, 0, 0, "The equation has 2 real solution(s): 0, 0");
    runTest(1, 4, 2, "No solution.");
    runTest(1, -4, 2, "The equation has 4 real solution(s): 0.765..., -0.765..., 1.847..., -1.847...");
    runTest(-2, 3, 4, "The equation has 2 real solution(s): 1.533..., -1.533...");
    runTest(1, -2, 1, "The equation has 2 real solution(s): 1, -1");


    return 0;
}